## Context

Flowarr is a Laravel 13 + Inertia React app. Stack: PHP 8.5, PostgreSQL 18, Redis 7, ffmpeg, mkvtoolnix, Vite (React + Tailwind), Laravel Wayfinder for typed routes. No Octane or Roadrunner — production HTTP is handled by nginx + PHP-FPM bundled in a single container.

Current state: No `Dockerfile` exists at repo root despite a GHCR publish workflow referencing it. The `docker-compose.yml` references a non-existent `Dockerfile.nginx` sidecar build. The `fix-production-docker` change designed a multi-stage Dockerfile based on Octane/Roadrunner that was never built. The goal is to ship a working self-host experience with the simplest possible production stack.

### Design constraints
- **Zero manual steps**: No `artisan` commands after container start. Entrypoint handles everything.
- **Minimal env vars**: Only `DB_PASSWORD` and `APP_KEY` required. Everything else has sensible defaults.
- **Traefik-native**: Labels for Traefik auto-discovery included in compose file. No additional proxy config.
- **Single compose file**: Copy one file, create `.env`, run `docker compose up`.

## Goals / Non-Goals

**Goals:**
- Working `Dockerfile` producing a ~200-300MB runtime image with PHP 8.5 FPM, nginx, ffmpeg, mkvtoolnix
- Entrypoint auto-handles APP_KEY (base64 prefix), runs migrations (opt-in), starts nginx + PHP-FPM
- `docker-compose.yml` with PostgreSQL + Redis + single app container, Traefik labels, healthchecks, persistent volumes
- nginx + PHP-FPM bundled in one container — no separate web server sidecar
- `.env.example.docker` documents all env vars with defaults and required markers
- GHCR publish workflow produces working images tagged `:vX.Y.Z`, `:latest`, `:edge`
- Alpine-based PHP-FPM image for minimal size

**Non-Goals:**
- Multi-arch builds (amd64 only)
- Changing Laravel Sail dev workflow
- GPU/hardware acceleration passthrough in Docker (host-level concern, already documented)
- Kubernetes manifests, Helm charts, or Terraform
- Queue worker container (self-hosters can add via `command:` override in compose)
- Octane, Roadrunner, FrankenPHP, Swoole, or any app server above PHP-FPM

## Decisions

### D1: Bundle nginx + PHP-FPM in a single container, remove separate sidecar

**Choice**: Ship one Docker image containing both nginx and PHP-FPM. The entrypoint starts both inside the same container. nginx listens on port 8080 and proxies PHP requests to php-fpm on port 9000. Traefik connects to nginx directly.
**Rationale**: The project does not use Octane or Roadrunner, so we need a real web server to serve PHP and static assets. Bundling nginx + PHP-FPM in one container avoids the complexity of two separate containers (shared volumes for public/, inter-container networking, multi-image build pipeline) while staying production-safe. nginx serves static files directly and handles FastCGI proxying to PHP-FPM for PHP requests.
**Alternatives**:
- Separate nginx sidecar container → rejected, adds complexity with shared volumes and dual build pipeline
- PHP built-in server (`php -S`) → rejected, single-threaded, unsuitable even for homelab traffic
- FrankenPHP → not installed, adds dependency; also requires downloading a Go binary
- Caddy → viable alternative to nginx but nginx is more widely understood for self-hosters

### D2: Alpine-based 3-stage build

**Choice**: `php:8.5-fpm-alpine` base for runtime. Stages: `composer` (PHP deps + wayfinder types), `assets` (Node frontend), `runtime` (assembly).
**Rationale**: Alpine cuts ~50MB vs bookworm. 3 stages isolate cache-busting (composer.lock, package-lock.json each bust their own stage). PHP-FPM is the base — we install nginx on top, no separate web server stage needed.
**Alternatives**: 
- 2-stage (build + runtime) → busts cache too often
- 3-stage with PHP in assets stage → doubles PHP install for wayfinder
- 4-stage with roadrunner binary download → not needed, no Roadrunner

### D3: APP_KEY handled by entrypoint (unset env var, write to .env)

**Choice**: Entrypoint reads `APP_KEY` from environment, ensures `base64:` prefix is present (adds it if missing), writes to `.env` file, then `unset APP_KEY` from process env. Laravel reads from `.env` exclusively.
**Rationale**: Laravel reads `$_ENV`/`$_SERVER`/`getenv()` before `.env`. If `APP_KEY` is set as a Docker env var, it takes precedence over the `.env` file — but the env var may lack the `base64:` prefix that Laravel's Encrypter requires. By unsetting the env var and writing to `.env`, we ensure consistent behavior regardless of how the user provides the key.
**Auto-generation**: If `APP_KEY` is empty or unset, entrypoint generates a random one via `php artisan key:generate`.
**Implementation sketch**:
```sh
if [ -z "${APP_KEY:-}" ] || [ "${APP_KEY}" = "base64:" ]; then
    php artisan key:generate --force
else
    RAW_KEY="${APP_KEY}"
    unset APP_KEY
    # Ensure base64: prefix
    case "$RAW_KEY" in
        base64:*) echo "APP_KEY=$RAW_KEY" >> .env ;;
        *) echo "APP_KEY=base64:${RAW_KEY}" >> .env ;;
    esac
fi
```

### D4: Entrypoint runs both nginx and PHP-FPM, no gosu needed

**Choice**: Entrypoint runs as root. It handles APP_KEY and migrations, then starts `php-fpm` in the background and `nginx` in the foreground. Signal trapping ensures clean shutdown.
**Rationale**: nginx and PHP-FPM both need root/privileged startup (nginx master process drops to nginx user automatically, PHP-FPM pools run as www-data). Running as root throughout is standard for `php:8.x-fpm-alpine` images.
**Alternatives**: `su-exec` / `gosu` → unnecessary, both nginx and PHP-FPM handle privilege dropping internally

### D5: Pre-warm caches at build time, entrypoint clears + refreshes after env substitution

**Choice**: Dockerfile `RUN` steps execute `config:cache`, `route:cache`, `event:cache`, `view:cache` after source copy. Entrypoint clears config cache (because .env changed), runs migrate, then re-caches config.
**Rationale**: Fast container start. Build-time caching means the image has route/event/view caches ready. Config cache must be rebuilt because DB_HOST, REDIS_HOST, APP_URL depend on deployment-specific env vars.
**Sequence**: `php-fpm` background start → `config:clear` → `migrate --force` → `config:cache` → `config:show app.key` (verify) → `nginx` foreground start

### D6: nginx config shipped in image, not generated at runtime

**Choice**: Ship `docker-production/nginx.conf` into the runtime image. nginx listens on port 8080, serves static files from `/var/www/html/public`, proxies `.php` requests to `127.0.0.1:9000` (PHP-FPM).
**Rationale**: Standard Laravel nginx config. Predictable, no runtime generation needed. Env vars for server_name can be set via `DOMAIN` or left as `_`.

### D7: Traefik labels on the app container

**Choice**: Traefik Docker labels live directly on the `flowarr` service in `docker-compose.yml`. nginx inside the container serves HTTP on port 8080. Traefik connects to it.
**Rationale**: The app container has nginx listening on port 8080 internally. Traefik connects directly. No separate Traefik-facing container needed. The existing `trustProxies(at: '*')` middleware already handles Traefik's `X-Forwarded-Proto` header.

## Architecture

```
┌───────────────────────────────────────────────────────────────┐
│                       Docker Host                             │
│                                                               │
│  ┌──────────┐    ┌──────────┐    ┌────────────────────────┐  │
│  │  Traefik  │    │  Redis   │    │       Flowarr          │  │
│  │ (ext net) │◄──►│  :6379   │    │  ┌──────────────────┐  │  │
│  │  :443     │    └──────────┘    │  │    nginx :8080    │  │  │
│  │  :80      │                    │  │     ↓ FastCGI      │  │  │
│  └─────┬─────┘                    │  │  PHP-FPM :9000    │  │  │
│        │                          │  │  + ffmpeg          │  │  │
│        │                          │  │  + mkvtoolnix     │  │  │
│        │                          │  └──────────────────┘  │  │
│        └──────────┬───────────────└───────────┬────────────┘  │
│                   ▼                           ▼               │
│           ┌──────────────┐           ┌──────────────┐        │
│           │  PostgreSQL  │           │  Queue Work  │        │
│           │    :5432     │           │  (separate   │        │
│           └──────────────┘           │   container  │        │
│                                       │   optional)  │        │
│                                       └──────────────┘        │
│                                                               │
│  Volumes: postgres-data, redis-data, flowarr-storage         │
└───────────────────────────────────────────────────────────────┘
```

## Risks / Trade-offs

- **[Alpine musl compatibility]** → PHP extensions may have issues on musl. Mitigation: Test all required extensions (pgsql, pdo_pgsql, bcmath, zip, intl, pcntl, redis) on php:8.5-cli-alpine. Fallback to bookworm-slim if critical extension fails.
- **[APP_KEY auto-generate loses sessions on restart]** → If user doesn't set APP_KEY, entrypoint generates one. Sessions encrypted with old key become unreadable after restart. Mitigation: Document APP_KEY as recommended, warn about session loss in compose comments. Key generation is a safety net, not the recommended path.
- **[Config cache rebuild at startup adds ~1s]** → Clears and re-caches config on every container start. Acceptable — 1 second is negligible compared to PostgreSQL connection pool warmup.
- **[Single container = both nginx and PHP-FPM crash if one fails]** → If PHP-FPM dies, nginx returns 502. Mitigation: simple healthcheck script that checks both processes. If either fails, the healthcheck fails and Docker restarts the container.
- **[nginx config edge cases]** → Poorly configured nginx can leak PHP source or cause 404s. Mitigation: Standard Laravel nginx config (`try_files $uri $uri/ /index.php?$query_string`), tested before shipping.
- **[Pre-warmed config cache vs dynamic .env]** → Already addressed in D5: entrypoint clears config cache, then re-caches after writing .env.
- **[APP_KEY auto-generate loses sessions on restart]** → If user doesn't set APP_KEY, entrypoint generates one. Sessions encrypted with old key become unreadable after restart. Mitigation: Document APP_KEY as recommended, warn about session loss in compose comments.
