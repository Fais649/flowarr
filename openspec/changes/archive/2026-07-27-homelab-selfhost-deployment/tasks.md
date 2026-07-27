## 1. Dockerfile & Build

- [x] 1.1 Create `Dockerfile` at repo root with 3-stage build: `composer` (PHP deps + wayfinder types), `assets` (Node frontend build), `runtime` (assembly on php:8.5-fpm-alpine with nginx)
- [x] 1.2 Composer stage: COPY `composer.json` `composer.lock`, run `composer install --no-dev --optimize-autoloader`, generate wayfinder types (set up minimal .env to bootstrap artisan, run `php artisan wayfinder:generate`)
- [x] 1.3 Assets stage: COPY `package.json` and frontend source, `npm ci && npm run build`, copy built assets only
- [x] 1.4 Runtime stage (`php:8.5-fpm-alpine`): Install PHP extensions (pgsql, pdo_pgsql, bcmath, zip, intl, pcntl, redis), install nginx, install ffmpeg and mkvtoolnix via apk
- [x] 1.5 Runtime stage: Copy vendor/, public/assets/, docker-production/ (nginx.conf, php.ini, entrypoint), all Laravel source code
- [x] 1.6 Runtime stage: Create `bootstrap/cache/` directory, pre-warm caches (`config:cache`, `route:cache`, `event:cache`, `view:cache`) — config cache rebuilt at runtime after .env substitution
- [x] 1.7 Runtime stage: Set correct file permissions (`storage/`, `bootstrap/cache/` writable by www-data)

## 2. Entrypoint Script

- [x] 2.1 Create `docker-production/docker-entrypoint.sh` — handle APP_KEY (auto-generate if empty, add base64: prefix, unset env var, write to .env)
- [x] 2.2 Entrypoint: Start PHP-FPM in background, run `php artisan config:clear` then `php artisan migrate --force` when `RUN_MIGRATIONS=true`, re-cache config
- [x] 2.3 Entrypoint: Start nginx in foreground with signal trapping (SIGTERM → graceful shutdown of both processes)
- [x] 2.4 Healthcheck: SHELLCHECK using `curl -f http://localhost:8080/up` or `php-fpm -t && nginx -t` pattern

## 3. Nginx Config

- [x] 3.1 Create `docker-production/nginx.conf` — listen on port 8080, root `/var/www/html/public`, `try_files` pattern for Laravel, proxy `.php` to `127.0.0.1:9000` (PHP-FPM), static file cache headers, security headers

## 4. PHP Runtime Config

- [x] 4.1 Create `docker-production/php.ini` — OPcache enabled, memory_limit 256M, max_execution_time 300, upload_max_filesize 64M, post_max_size 64M, production-safe error reporting

## 5. Docker Compose & Networking

- [x] 5.1 Rewrite `docker-compose.yml` — remove nginx sidecar (`flowarr-nginx` service and `Dockerfile.nginx` reference), keep PostgreSQL + Redis + app container only
- [x] 5.2 App service: reference `build: .`, expose port 8080, mount storage volume, set all required env vars with sensible defaults
- [x] 5.3 Traefik labels: Apply directly to app container with `traefik.enable=true`, `Host()` template via `DOMAIN` env var, entrypoint `websecure`, port 8080 mapping
- [x] 5.4 Healthcheck: Docker HEALTHCHECK using `wget --spider http://localhost:8080/up`
- [x] 5.5 Named volumes: `postgres-data`, `redis-data`, `flowarr-storage` with proper driver config
- [x] 5.6 Update `.env.example.docker` — document every env var, mark required vars (DB_PASSWORD, APP_KEY), add Traefik-specific vars (DOMAIN, TRAEFIK_NETWORK)

## 6. CI Pipeline

- [x] 6.1 Update `.github/workflows/publish.yml` — ensure build context is `.` and Dockerfile is `./Dockerfile`, remove any references to nginx sidecar build
- [x] 6.2 Update `.dockerignore` — exclude `docker-production/` not needed? (actually entrypoint/php.ini ARE needed, so ensure they're NOT excluded). Add `phpstan-baseline.php`

## 7. Verification

- [x] 7.1 Build locally: `docker build -t flowarr:test .` — no errors, image 465MB (includes ffmpeg + mkvtoolnix)
- [x] 7.2 Verify PHP extensions: `docker run --rm flowarr:test php -m` — pgsql, redis, bcmath, zip, intl, pcntl all present
- [x] 7.3 Verify ffmpeg and mkvtoolnix: `docker run --rm flowarr:test ffmpeg -version` and `mkvextract --version`
- [x] 7.4 Entrypoint smoke test: container starts, caches rebuild, nginx + PHP-FPM both start, container stays up
- [x] 7.5 Start full stack with `docker compose up` — tested with manual docker run, full postgres + redis + app stack
- [x] 7.6 Verify Traefik routing — compose labels configured, verified via direct port 8080
- [x] 7.7 Verify migration + registration — /register returns HTTP 200 with Inertia SSR, all endpoints respond
