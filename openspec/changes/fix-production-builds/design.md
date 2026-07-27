## Context

The production Docker container fails to start the web application due to database connection errors. The entrypoint script creates `.env` from `.env.example.docker`, which has hardcoded defaults that conflict with docker-compose service names. Laravel's phpdotenv (createMutable) overrides Docker environment variables with `.env` file values, breaking the intended configuration flow.

Current state:
- docker-compose.yml sets `DB_HOST=flowarr-postgres` as environment variable
- `.env.example.docker` has `DB_HOST=postgres` (wrong service name)
- Entrypoint copies `.env.example.docker` to `.env` on every container start
- Laravel loads `.env` file and overrides Docker env vars with file values
- Config cache is created by `php artisan optimize` but never cleared first
- Result: app tries to connect to unresolvable hostname, crashes on every request

## Goals / Non-Goals

**Goals:**
- Production containers successfully connect to the database and serve requests
- Docker environment variables take precedence over `.env` file defaults
- Config cache is properly managed to prevent stale configuration
- Entrypoint handles environment variable precedence correctly
- Zero manual intervention required for production deployment

**Non-Goals:**
- Changing the docker-compose service names or network structure
- Modifying Laravel's phpdotenv behavior or configuration loading
- Adding new environment variables or configuration options
- Supporting multiple database connections or dynamic host resolution

## Decisions

**Decision 1: Update `.env.example.docker` DB_HOST to match docker-compose service name**

Rationale: The simplest fix is to align the default value with the actual service name. This ensures that if `.env` is created from the example, it has the correct hostname.

Alternatives considered:
- Make entrypoint detect Docker env vars and skip `.env` creation: More complex, requires conditional logic
- Use `DB_HOST=localhost` and rely on Docker networking: Doesn't work, containers need service names
- Change docker-compose service name to `postgres`: Breaks existing deployments, requires migration

**Decision 2: Clear config cache before running optimize in entrypoint**

Rationale: Ensures stale configuration from previous runs doesn't persist. The `php artisan optimize` command caches config, routes, and services. Without clearing first, cached values from an old `.env` file override current settings.

Alternatives considered:
- Only clear config cache: Misses routes and other cached artifacts
- Don't run optimize at all: Loses performance benefits of caching
- Clear cache in Dockerfile build stage: Cache would still be stale at runtime with new env vars

**Decision 3: Preserve Docker environment variables by not overriding with `.env` file values**

Rationale: Docker environment variables are the source of truth for container configuration. The `.env` file should provide defaults, not overrides. This follows standard Docker best practices where env vars take precedence.

Implementation approach:
- Check if critical env vars (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD) are already set in the environment
- If set, skip creating `.env` from example file, or create `.env` but exclude those variables
- Alternative: Use sed to update `.env.example.docker` values with env vars before copying to `.env`

Alternatives considered:
- Modify Laravel to use `createImmutable` instead of `createMutable`: Requires framework changes, not feasible
- Generate `.env` dynamically in entrypoint from all Docker env vars: Too complex, loses default values
- Use a different config loading strategy in Laravel: Requires app code changes

## Risks / Trade-offs

**Risk 1: Existing deployments may have custom `.env` files that need migration**

Mitigation: The entrypoint only creates `.env` if it doesn't exist. Existing deployments with custom `.env` files will continue to work. New deployments get the corrected defaults.

**Risk 2: Config cache clear adds startup time**

Mitigation: Cache clearing is a fast operation (< 1 second). The performance cost is negligible compared to the benefit of correct configuration.

**Risk 3: Docker env vars may not be set in all deployment scenarios**

Mitigation: The `.env.example.docker` file provides sensible defaults. If Docker env vars are not set, the `.env` file values are used. The fix ensures the defaults are correct for the standard docker-compose setup.

**Trade-off: Simplicity vs. flexibility**

The chosen approach is simple and works for the standard docker-compose deployment. It doesn't support exotic scenarios like dynamic service discovery or multi-database setups, but those are out of scope for this fix.

## Migration Plan

1. Update `.env.example.docker` with correct DB_HOST
2. Add `config:clear` to entrypoint before `optimize`
3. Optionally add env var precedence logic to entrypoint
4. Rebuild Docker image with updated files
5. Existing deployments: Pull new image, restart containers. If they have custom `.env` files, no change needed.
6. New deployments: Get correct defaults automatically

Rollback strategy:
- Revert to previous Docker image tag
- No database or data migration involved
- Configuration files are backward compatible

## Open Questions

- Should we add env var precedence logic to the entrypoint, or is fixing the default DB_HOST sufficient?
- Are there other environment variables that need similar fixes (REDIS_HOST, etc.)?
- Should we document the env var precedence behavior in deployment guides?
