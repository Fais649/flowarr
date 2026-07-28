## Why

Production Docker container fails to start because the application cannot connect to the database. The error log shows `SQLSTATE[08006] [7] could not translate host name "pgsql" to address`, indicating the DB_HOST environment variable is not being set correctly. The entrypoint script creates `.env` from `.env.example.docker`, which has `DB_HOST=postgres`, but the docker-compose service is named `flowarr-postgres`. Additionally, Laravel's phpdotenv overrides Docker environment variables with `.env` file values, so even though docker-compose sets `DB_HOST=flowarr-postgres`, it gets overridden by the `.env` file's value.

## What Changes

- Fix `.env.example.docker` to set `DB_HOST=flowarr-postgres` to match the docker-compose service name
- Update entrypoint script to preserve Docker environment variables instead of overriding them with `.env` file values
- Clear config cache before running `php artisan optimize` to prevent stale configuration
- Verify the production build works end-to-end with correct database connectivity

## Capabilities

### New Capabilities

- `docker-env-handling`: Fix environment variable precedence in Docker entrypoint to respect container environment variables over `.env` file values

### Modified Capabilities

(none — existing capabilities don't need requirement changes, just bug fixes)

## Impact

- **Docker deployment**: Production containers will be able to connect to the database
- **Entrypoint script**: Modified to handle environment variables correctly
- **Configuration files**: `.env.example.docker` updated with correct service name
- **Cache management**: Config cache cleared before optimization to prevent stale values
