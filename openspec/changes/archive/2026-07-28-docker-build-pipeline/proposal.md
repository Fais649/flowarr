## Why

Flowarr is an open-source project, but currently the only way to run it is via Laravel Sail — which requires PHP, Composer, Node, and a full dev toolchain on the host. Self-hosters should be able to pull a versioned Docker image and define the service in their `docker-compose.yml` without any of that. A proper build pipeline publishing images to GHCR (GitHub Container Registry) solves this.

## What Changes

- **Production Dockerfile**: Multi-stage build producing a lean runtime image with PHP-FPM, Nginx/Caddy, compiled assets, and vendor dependencies — no dev toolchain, no Sail, no source code
- **`.dockerignore`**: Exclude dev artifacts from the build context
- **`Dockerfile` at repo root**: Single entry point for production image builds
- **GitHub Actions workflow**: Build and publish versioned Docker images on tags and the default branch
- **Versioning strategy**: Tags published as `:vX.Y.Z` and `:latest` for stable; `:edge` for main branch
- **README update**: Quickstart section with `docker compose` snippet replacing the Sail-only instructions

## Capabilities

### New Capabilities
- `docker-image-build`: Production Dockerfile, multi-stage build, .dockerignore, and image publication pipeline

### Modified Capabilities
<!-- No existing spec caps are changing; we're adding a new infra capability -->

## Impact

- **New files**: `Dockerfile` (root), `.dockerignore`, `.github/workflows/publish.yml`
- **Modified files**: `README.md` (add self-host quickstart)
- **Dependencies**: GitHub Container Registry (GHCR) for image hosting
- **No breaking changes**: Existing Sail-based dev workflow untouched
