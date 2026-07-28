## 1. Prerequisites & Config

- [x] 1.1 Install Laravel Octane: `composer require laravel/octane spiral/roadrunner` and run `php artisan octane:install --server=roadrunner`
- [x] 1.2 Create `.rr.yaml` config for Roadrunner at project root (workers, http port, static assets, logs)
- [x] 1.3 Add a `/health` route to `routes/web.php` returning `{"status":"ok"}` for container health checks
- [x] 1.4 Create production `.env.example.docker` with defaults suitable for containerized deployment

## 2. Docker Build Files

- [x] 2.1 Create `.dockerignore` excluding dev/CI artifacts (node_modules, vendor, .git, tests, .github, .storybook, stubs, docker/, openspec/, *.md, IDE configs)
- [x] 2.2 Create multi-stage `Dockerfile` at repo root with build stages: composer vendor, npm assets, roadrunner binary, runtime (php:8.5-cli-bookworm)
- [x] 2.3 Create `docker-entrypoint.sh` — handles env var setup, optional migrate, runs `php artisan octane:start --server=roadrunner --host=0.0.0.0 --port=80`
- [x] 2.4 Create production `php.ini` override with production-safe settings (OPcache, memory limit, max execution time)

## 3. CI Pipeline

- [x] 3.1 Create `.github/workflows/publish.yml` — trigger on `v*` tags and `main` pushes
- [x] 3.2 Configure build step using Docker Buildx with cache
- [x] 3.3 Configure GHCR login via `GITHUB_TOKEN` with `write:packages` permission
- [x] 3.4 Tag images: `:vX.Y.Z` + `:latest` on tag, `:edge` on main push
- [x] 3.5 Add OCI labels for provenance (source, revision, version, title)

## 4. Documentation

- [x] 4.1 Update `README.md` — add "Self-Hosting" section with docker compose quickstart, required env vars, volume mounts, and PostgreSQL service setup
- [x] 4.2 Add a reference to the GHCR image badge in the README header

## 5. Verification

- [x] 5.1 Build image locally: `docker build -t flowarr:test .` and verify no dev artifacts in final image
- [x] 5.2 Verify PHP extensions are present: `docker run --rm flowarr:test php -m`
- [x] 5.3 Verify container starts and responds on health endpoint
- [x] 5.4 Verify `.dockerignore` actually excludes dev files from build context
