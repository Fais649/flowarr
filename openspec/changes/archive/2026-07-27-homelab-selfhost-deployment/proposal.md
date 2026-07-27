## Why

Flowarr has a production Docker image pipeline defined (GHCR publish workflow, `.env.example.docker`, `.dockerignore`) but no actual `Dockerfile` at repo root — the build is broken. The existing `docker-compose.yml` references images that don't exist and build files that haven't been created. For an open-source self-hostable app, this is a blocker. Homelab users expect: copy `docker-compose.yml`, set env vars, `docker compose up` — no manual artisan commands, no building from source, no missing files.

## What Changes

- **Rewrite `Dockerfile`** — production multi-stage build (composer → assets → runtime) producing a single container with nginx + PHP-FPM, ffmpeg, and mkvtoolnix for media processing
- **Create `docker-production/docker-entrypoint.sh`** — auto-handles APP_KEY, runs migrations on startup (`RUN_MIGRATIONS=true`), starts nginx + PHP-FPM
- **Create `docker-production/php.ini`** — production-safe PHP settings (OPcache, memory limit, etc.)
- **Create `docker-production/nginx.conf`** — nginx server config proxying to PHP-FPM, serving static assets directly
- **Rewrite `docker-compose.yml`** — simplified for self-hosters: PostgreSQL + Redis + single app container with nginx + PHP-FPM, clean Traefik labels, sensible defaults, clear env var docs
- **Update `.github/workflows/publish.yml`** — point at the now-existing `Dockerfile`, add `.env` build args for baking non-sensitive config at build time
- **Add `phpstan-baseline.php` to `.dockerignore`** (present on disk but missing from ignore list)

## Capabilities

### New Capabilities
- `selfhost-deployment`: Single-file docker-compose.yml with env var documentation, automatic migrations on startup, Traefik integration, and zero manual artisan commands

### Modified Capabilities
- `ci-pipeline`: The build/publish workflow gains a real Dockerfile target; image tags now produce working containers instead of failing at build time

## Impact

- **New files**: `Dockerfile`, `docker-production/docker-entrypoint.sh`, `docker-production/php.ini`, `docker-production/nginx.conf`
- **Modified files**: `docker-compose.yml`, `.github/workflows/publish.yml`, `.dockerignore`
- **Removed files**: `Dockerfile.nginx` (no longer needed — nginx config built into single container)
- **Dependencies**: No new dependencies. Uses official `php:8.5-fpm-alpine`, `composer`, `node:22` build images
- **Breaking change**: `docker-compose.yml` restructured — single app container with internal nginx + PHP-FPM, Traefik routes to port 8080 (nginx)
