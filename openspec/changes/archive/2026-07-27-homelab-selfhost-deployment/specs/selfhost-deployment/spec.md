## ADDED Requirements

### Requirement: Single-file deployment
The system SHALL be deployable with a single `docker-compose.yml` file and a `.env` file. No manual Artisan commands SHALL be required after container startup.

#### Scenario: First-time deploy
- **WHEN** a user copies `docker-compose.yml` to an empty directory
- **WHEN** the user creates a `.env` file with required variables (DB_PASSWORD, APP_KEY)
- **WHEN** the user runs `docker compose up`
- **THEN** the application SHALL become available at the configured URL
- **THEN** the database SHALL be migrated automatically
- **THEN** a first-user registration page SHALL be accessible

### Requirement: Entrypoint automation
The container entrypoint SHALL handle all boot-time setup: APP_KEY prefix management, database migrations (opt-in via `RUN_MIGRATIONS=true`), config cache refresh, and web server start (nginx + PHP-FPM).

#### Scenario: APP_KEY auto-handling
- **WHEN** the container starts with `APP_KEY` set in environment (raw base64 or empty)
- **THEN** the entrypoint SHALL ensure the `base64:` prefix is present and write the correct value to `.env`
- **THEN** Laravel's encrypter SHALL function correctly

#### Scenario: Migrations on startup
- **WHEN** the container starts with `RUN_MIGRATIONS=true`
- **THEN** the entrypoint SHALL run `php artisan migrate --force` before starting the web server
- **WHEN** the container starts with `RUN_MIGRATIONS=false` or unset
- **THEN** the web server SHALL start without running migrations

### Requirement: Traefik reverse proxy support
The `docker-compose.yml` SHALL include Traefik service labels for automatic routing, respecting Traefik's external network and standard entrypoints.

#### Scenario: Traefik routing
- **WHEN** the stack is deployed on a Docker host with a running Traefik instance
- **THEN** the `flowarr` container SHALL register with Traefik using `traefik.enable=true`
- **THEN** the router rule SHALL use `Host()` template set via `DOMAIN` env variable
- **THEN** the service SHALL be reachable on port 8080 (nginx HTTP)

### Requirement: Health checks
The container SHALL expose a `/up` healthcheck endpoint and the compose file SHALL define Docker healthcheck configuration.

#### Scenario: Healthcheck passes
- **WHEN** the container is running and healthy
- **THEN** `curl -f http://localhost/up` SHALL return HTTP 200 within the healthcheck interval

### Requirement: Media processing dependencies
The runtime image SHALL include ffmpeg and mkvtoolnix for media file processing.

#### Scenario: ffmpeg available
- **WHEN** a media processing job runs inside the container
- **THEN** ffmpeg SHALL be available at `/usr/bin/ffmpeg`

#### Scenario: mkvtoolnix available
- **WHEN** a subtitle extraction job runs inside the container
- **THEN** `mkvextract` SHALL be available at `/usr/bin/mkvextract`

### Requirement: Persistent storage
The compose file SHALL define named volumes for PostgreSQL data, Redis data, and application storage (session files, logs, cache).

#### Scenario: Data survives restart
- **WHEN** the stack is stopped with `docker compose down` and restarted with `docker compose up`
- **THEN** all database records, Redis data, and application logs SHALL be preserved

### Requirement: Env var documentation
The `.env.example.docker` file SHALL document every environment variable used by the compose file, with comments indicating required vs optional and default values.

#### Scenario: Complete documentation
- **WHEN** a user opens `.env.example.docker`
- **THEN** every env variable referenced in `docker-compose.yml` SHALL be listed with a comment explaining its purpose
- **THEN** required variables SHALL be marked with `(required)`
