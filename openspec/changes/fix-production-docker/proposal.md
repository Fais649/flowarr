## Why

The current production Docker setup builds successfully but the webapp inside the container doesn't work. Multiple critical bugs prevent the application from functioning: APP_KEY environment variable conflicts with .env file handling (Laravel reads env vars first, bypassing the base64: prefix logic), OCTANE_HTTPS=true in .env.example.docker contradicts the HTTP healthcheck, the assets build stage is wastefully installing full PHP + Node.js just to run wayfinder, and file permission issues cause cache failures. A complete rewrite is needed to deliver a working, fast, and small production image.

## What Changes

- **Complete Dockerfile rewrite**: New multi-stage build with proper stage ordering, correct file permissions, and working entrypoint
- **Fixed APP_KEY handling**: Entrypoint correctly manages base64: prefix without env var conflicts
- **Fixed OCTANE_HTTPS**: Remove misleading default, align with actual TLS termination strategy (Traefik)
- **Optimized build stages**: Assets stage uses lightweight Node.js image, wayfinder types generated once and cached
- **Proper file permissions**: All runtime directories (storage, bootstrap/cache) created with correct ownership
- **Complete runtime assembly**: All necessary directories (database/seeders, lang, bootstrap/cache) properly copied
- **Working healthcheck**: HTTP endpoint matches actual server protocol

## Capabilities

### New Capabilities
- `production-docker`: Working production Docker setup with multi-stage build, proper environment handling, and optimized image size

### Modified Capabilities
<!-- No existing spec-level capabilities are changing -->

## Impact

- **New/Modified files**: `Dockerfile`, `docker/prod/docker-entrypoint.sh`, `.env.example.docker`, `docker-compose.yml`
- **Runtime behavior**: App will actually start and respond to requests
- **Build performance**: Faster builds due to optimized stage ordering and caching
- **Image size**: Smaller final image (~200-300MB vs current bloated build)
- **Dependencies**: No new dependencies, just fixes to existing Docker infrastructure
