## Context

Flowarr currently only runs via Laravel Sail (a dev-oriented Docker Compose wrapper). The production-like `compose.yaml` mounts the entire source tree as a volume, runs `artisan serve` via supervisord, and depends on the full dev toolchain (PHP 8.5 CLI, Composer, Node 24, pnpm, bun, Playwright, ffmpeg, mkvtoolnix, GPU drivers, PostgreSQL client). This is unsuitable for self-hosters who expect `docker pull ghcr.io/fais/flowarr:v1.0.0 && docker compose up`.

The existing Sail Dockerfile at `docker/8.5/Dockerfile` is 100+ lines of Ubuntu 24.04 provisioning — it installs every PHP extension, dev tools, GPU drivers, and multiple JS runtimes. A production image should be lean, multi-stage, and contain only what's needed at runtime.

## Goals / Non-Goals

**Goals:**
- Single `Dockerfile` at repo root for production image builds
- Multi-stage build: compile PHP deps + frontend assets in build stages, copy only runtime essentials into final image
- Runtime image based on `php:8.5-fpm-alpine` or `php:8.5-fpm-bookworm` (~150-300 MB vs current ~1.5 GB)
- Include only runtime PHP extensions (pgsql, gd, bcmath, redis, mbstring, xml, zip, curl)
- Include ffmpeg and mkvtoolnix for media processing
- Exclude dev tools: Composer, Node, npm/pnpm/bun, Playwright, GPU drivers, compiler toolchain
- `.dockerignore` to exclude dev artifacts (node_modules, vendor, tests, etc.)
- GitHub Actions workflow that builds and pushes versioned images to GHCR
- Tag scheme: `:vX.Y.Z` for release tags, `:latest` for latest stable, `:edge` for main branch
- README update with self-host `docker compose` quickstart

**Non-Goals:**
- Not replacing the Sail-based dev workflow — developers still use Sail
- Not adding Kubernetes manifests, Helm charts, or terraform configs
- Not building for multiple architectures (amd64 only initially)
- Not setting up a private registry or mirror
- Not automating DB migration on container start (user runs `artisan migrate` manually or via init container)

## Decisions

| Decision | Choice | Alternatives Considered | Rationale |
|----------|--------|------------------------|-----------|
| Base image | `php:8.5-cli-bookworm` | `php:8.5-fpm-bookworm`, `ubuntu:24.04` | Octane + Roadrunner uses CLI SAPI, not FPM. bookworm-slim provides needed PHP extensions. Offical PHP images avoid PPA dependency. |
| Asset compilation | Build stage with `node:22-bookworm` | Compile on host, commit assets | Node assets need npm; keeping them out of runtime image is clean. Build stage ensures reproducible builds regardless of host environment. |
| PHP extension install | `docker-php-ext-install` + `pecl` | apt from ondrej PPA | Official PHP images ship `docker-php-ext-*` helpers — more portable, no PPA dependency, smaller. |
| Web server | Laravel Octane + Roadrunner | PHP-FPM + Caddy/Nginx, FrankenPHP, Swoole | Roadrunner is a single Go binary — no extra web server needed, no supervisord. Keeps PHP in memory between requests (no bootstrap per request), far better throughput than FPM. Go binary is trivial to download in build stage. |
| Entrypoint | Custom shell script that runs migrations (optional), then `php artisan octane:start --server=roadrunner --host=0.0.0.0 --port=80` | Init container for migrations | Single entrypoint is simpler for self-hosters. Roadrunner manages its own worker processes — no supervisord required. |
| Image registry | GHCR (ghcr.io/fais/flowarr) | Docker Hub | GHCR is free for public images, already in the GitHub ecosystem, no extra credentials for the CI. |
| Tag scheme | `vX.Y.Z` + `latest` + `edge` | Semver-only, `main`-branch | Standard OSS pattern. `edge` gives early adopters the latest main. |

## Flow

```
[Git Tag: v1.2.3] ──▶ GitHub Actions ──▶ Checkout
                                              │
                                              ▼
                                   Build Stage 1: vendor/
                                   ┌─────────────────────┐
                                   │ composer install     │
                                   │ --no-dev --optimize  │
                                   │ + octane:install     │
                                   └─────────┬───────────┘
                                             ▼
                                   Build Stage 2: assets/
                                   ┌─────────────────────┐
                                   │ npm ci              │
                                   │ npm run build        │
                                   └─────────┬───────────┘
                                             ▼
                                   Build Stage 3: roadrunner/
                                   ┌──────────────────────────┐
                                   │ download roadrunner      │
                                   │ binary (Go) from GH      │
                                   │ ./vendor/bin/rr          │
                                   └─────────┬────────────────┘
                                             ▼
                                   Final Stage: runtime
                                   ┌──────────────────────────┐
                                   │ php:8.5-cli-bookworm     │
                                   │ + runtime extensions     │
                                   │ + ffmpeg, mkvtoolnix     │
                                   │ + vendor/ (incl rr bin)  │
                                   │ + public/assets/         │
                                   │ + artisan + app/         │
                                   │ + .rr.yaml               │
                                   └─────────┬────────────────┘
                                             ▼
                                  ┌──────────────────────────┐
                                  │  Push to GHCR:           │
                                  │  ghcr.io/fais/flowarr    │
                                  │  :v1.2.3, :latest        │
                                  └──────────────────────────┘
```

Also runs on push to `main` → tags as `:edge`. No supervisord needed — Roadrunner manages PHP worker processes natively.

## Risks / Trade-offs

- **[Size]** PHP CLI + Roadrunner + ffmpeg + mkvtoolnix produces ~400-500 MB. Mitigation: bookworm-slim variant, clean apt cache in same RUN layer. Roadrunner is a single ~30 MB Go binary — smaller than Caddy + FPM.
- **[GPU]** Image won't include Intel/NVIDIA GPU driver stacks — host-level concern. Users pass `/dev/dri` or NVIDIA runtime. Image has ffmpeg with software codecs only.
- **[Migrations]** `artisan migrate --force` in entrypoint risks race conditions with multiple replicas. Mitigation: opt-in via `RUN_MIGRATIONS=true` env var.
- **[PHP 8.5 availability]** PHP 8.5 may not be in official `php:` images yet. Mitigation: fallback to `php:8.4-cli-bookworm`, parameterize base tag.
- **[Queue workers]** Default image starts Octane (web only). Self-hosters override command: `php artisan queue:work --daemon`.
- **[Roadrunner binary]** Roadrunner binary is architecture-specific (linux amd64). Mitigation: Pin version in Dockerfile, verify SHA256 checksum.

## Open Questions

1. Should the image include a default `php.ini` with production-safe settings? (Yes — include a basic `production.ini` override)
2. Should we pin Roadrunner version via `.rr.yaml` or `composer.json`? (Via `composer require spiral/roadrunner:*` — versioned through composer.lock)
3. Should the `.rr.yaml` config be included in the image or generated at startup? (Ship a default `.rr.yaml` in the repo + override via env vars)
