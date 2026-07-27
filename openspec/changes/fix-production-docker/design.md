## Context

Flowarr is a Laravel 13 + Inertia React app using Octane/Roadrunner as the production HTTP server. The existing production Docker setup (`Dockerfile` + `docker-compose.yml` + `docker-production/`) was created by a previous change but fails at runtime — containers start but the webapp doesn't respond. Root cause analysis identified 5+ critical bugs in environment handling, file permissions, and build stage design.

Current stack: PHP 8.5, PostgreSQL, Redis, ffmpeg, mkvtoolnix, Vite (React + Tailwind), Laravel Wayfinder for typed routes, Traefik for reverse proxy.

### Critical Bugs in Current Setup

1. **APP_KEY env var vs .env file**: docker-compose sets `APP_KEY` as a raw env var. Laravel reads `$_ENV`/`$_SERVER`/`getenv()` BEFORE loading `.env` file. The entrypoint adds `base64:` prefix and writes it to `.env`, but the unprefixed env var still takes precedence. Laravel's Encrypter requires `base64:` prefix — encryption, sessions, and cookies all break silently.

2. **OCTANE_HTTPS=true mismatch**: `.env.example.docker` defaults to `OCTANE_HTTPS=true`. This tells Octane the URL scheme is HTTPS for link generation, but Roadrunner serves plain HTTP on port 8080. Traefik handles TLS termination externally. The healthcheck uses `curl -f http://localhost:8080/health` — this works at the Roadrunner level but `OCTANE_HTTPS=true` causes redirect loops when Traefik forwards the `X-Forwarded-Proto: https` header.

3. **Entrypoint file ownership**: Entrypoint runs as root. `php artisan optimize`, `php artisan migrate`, and `php artisan config:clear` create cache files owned by root. Roadrunner worker processes inherit root ownership (no USER switch), so this works today but is fragile — any future switch to www-data breaks immediately.

4. **Missing bootstrap/cache directory**: Runtime stage `COPY` commands never create `bootstrap/cache/`. The `RUN rm -f bootstrap/cache/packages.php` assumes it exists from `COPY bootstrap/`, but `.gitignore` in `bootstrap/cache/` may prevent it from being in the build context. Without this directory, `php artisan optimize` fails silently.

5. **Assets stage over-engineering**: The `assets` stage installs full PHP 8.5 with all extensions (pgsql, pdo_pgsql, bcmath, zip, sockets) just to run the Wayfinder Vite plugin. Wayfinder generates TypeScript route types from PHP — the plugin needs artisan available. But this doubles the PHP extension install cost across stages.

6. **Incomplete directory copy**: Runtime stage copies `database/migrations/` but not `database/seeders/`. The composer autoloader maps `Database\Seeders\` namespace — if any production code references seeder classes, they're missing.

7. **`node_modules` cross-stage pollution**: `COPY --from=vendor /app .` in assets stage copies vendor's PHP `node_modules` (if any from composer plugins) plus the full app, then `COPY package.json` overwrites. The npm ci then installs in a dirty tree.

## Goals / Non-Goals

**Goals:**
- Webapp actually starts and responds to HTTP requests inside the container
- APP_KEY handling works correctly regardless of how user provides it (raw, base64-prefixed, or empty for auto-generate)
- Build image ≤ 300MB compressed (from ~500MB+ current)
- Fast rebuilds via proper layer caching (vendor, assets, source separated)
- All Laravel runtime requirements met (cache dirs, permissions, config optimization)
- Compatible with existing docker-compose.yml Traefik integration
- ffmpeg and mkvtoolnix available for media processing

**Non-Goals:**
- Multi-arch builds (amd64 only)
- Replacing Traefik with built-in TLS
- Changing the Sail-based dev workflow
- Adding CI/CD pipeline changes (separate concern)
- GPU/hardware acceleration in container (host-level concern)

## Decisions

### D1: Use `php:8.5-cli-alpine` instead of `php:8.5-cli-bookworm`

**Choice**: Alpine-based PHP image for all stages.
**Rationale**: ~50MB base vs ~100MB for bookworm. Combined with not installing dev extensions in the assets stage, this cuts image size significantly. Alpine's musl is compatible with all required PHP extensions.
**Alternatives considered**: 
- `php:8.5-fpm-alpine` — rejected because we use Octane/Roadrunner (CLI SAPI), not FPM
- Distroless — rejected because we need shell for entrypoint script and artisan commands

### D2: 4-stage build: `composer` → `assets` → `roadrunner` → `runtime`

**Choice**: Separate each concern into its own stage with clear cache boundaries.
**Rationale**: 
- `composer` stage: Only busts on composer.lock changes. Downloads RR binary here too (it's a PHP-driven download).
- `assets` stage: Only busts on package-lock.json or frontend source changes. Uses lightweight Node image + copies wayfinder output from composer stage.
- `roadrunner` stage: Separate small stage just for the RR binary download (pure HTTP fetch, cached by version).
- `runtime` stage: Assembly only. Copies artifacts from previous stages.

**Key difference from current**: Assets stage does NOT install PHP. Wayfinder types are pre-generated in the composer stage and copied as static files. The Vite wayfinder plugin reads them but doesn't need PHP to regenerate.

**Alternatives considered**:
- 2-stage (build + runtime) — rejected, busts cache too often
- 3-stage (current approach) — rejected, assets stage installs unnecessary PHP

### D3: Entrypoint handles APP_KEY by unsetting env var and writing to .env only

**Choice**: At container start, unset `APP_KEY` from the process environment, then write the correctly-prefixed value to `.env` file. Laravel then reads from `.env` file exclusively.
**Rationale**: Eliminates the env var vs .env precedence conflict. Single source of truth.
**Implementation**:
```sh
# Read raw APP_KEY from env, compute prefixed version, then unset
RAW_KEY="${APP_KEY}"
unset APP_KEY
# Write to .env with base64: prefix
```
**Alternatives considered**:
- Export corrected env var — rejected because other env vars (DB_*, REDIS_*) still need to flow through
- Use `sed` to modify .env in place — rejected, current grep-based approach works fine

### D4: Remove OCTANE_HTTPS from .env.example.docker, use FORCE_HTTPS instead

**Choice**: `OCTANE_HTTPS` controls URL generation scheme, not server TLS. Remove it from defaults. Add `APP_FORCE_HTTPS=true` for production (Laravel middleware redirects HTTP→HTTPS). Trust proxy headers from Traefik.
**Rationale**: `OCTANE_HTTPS=true` was a misunderstanding — it doesn't make Roadrunner serve HTTPS. Traefik handles TLS termination and forwards `X-Forwarded-Proto: https`. Laravel's `TrustProxies` middleware (already configured with `at: '*'`) handles the rest.

### D5: Run entrypoint as root, then exec as www-data

**Choice**: Container runs entrypoint as root for migration/optimization, then `exec gosu www-data php artisan octane:start` to drop privileges.
**Rationale**: Migrations and cache warming need write access to storage/bootstrap. Roadrunner workers run as www-data for security. gosu is lighter than su-exec and already standard in Docker PHP images.
**Alternative**: Run everything as root (current) — fragile, security concern.

### D6: Pre-warm all caches at build time, entrypoint only refreshes dynamic state

**Choice**: Dockerfile RUN step executes `php artisan config:cache`, `php artisan route:cache`, `php artisan event:cache`, `php artisan view:cache` after all source is copied. Entrypoint only runs `php artisan migrate` and clears config cache if .env changed.
**Rationale**: Build-time caching = faster container starts. Config cache must be cleared in entrypoint because .env values (DB_HOST, REDIS_HOST) vary per deployment.

## Risks / Trade-offs

- **[Alpine musl compatibility]** → Some PHP extensions may have issues on musl. Mitigation: Test all required extensions (pgsql, pdo_pgsql, bcmath, zip, intl, pcntl, redis) in Alpine. Fallback to slim-bookworm if critical extension fails.
- **[gosu adds complexity]** → Extra binary in image. Mitigation: gosu is 1.5MB, well-tested, standard practice. Alternative: `exec su-exec` or `exec runuser`.
- **[Pre-warmed config cache vs dynamic .env]** → `config:cache` at build time means DB_HOST etc. from .env won't be in cached config. Mitigation: Entrypoint clears config cache before starting Octane, then re-caches after .env is written. The 1-second startup cost is acceptable.
- **[Wayfinder plugin needs PHP types at build time]** → If wayfinder plugin in Vite tries to call artisan during `npm run build`, it needs a working PHP environment in the assets stage. Mitigation: Pre-generate wayfinder types in composer stage, copy as static files. Vite plugin should detect existing types and skip regeneration. Verify during implementation.
