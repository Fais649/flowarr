## ADDED Requirements

### Requirement: Database connection in production containers
The production Docker container MUST successfully connect to the PostgreSQL database using the hostname configured via Docker environment variables.

#### Scenario: Container starts with docker-compose defaults
- **WHEN** a production container starts using the provided docker-compose.yml
- **THEN** the application successfully connects to the database at the `flowarr-postgres` service
- **AND** the web application is reachable and serves requests without database connection errors

#### Scenario: DB_HOST environment variable is set
- **WHEN** the Docker environment variable `DB_HOST` is set to a custom value
- **THEN** the application uses that value for database connection
- **AND** the value is not overridden by defaults from `.env.example.docker`

### Requirement: Configuration cache management
The entrypoint script MUST clear the configuration cache before running optimization commands to prevent stale configuration from persisting across container restarts.

#### Scenario: Container restarts with updated environment variables
- **WHEN** a container restarts with different environment variable values
- **THEN** the configuration cache is cleared before optimization
- **AND** the application uses the new environment variable values, not cached values from previous runs

#### Scenario: Fresh container deployment
- **WHEN** a new container is deployed from a Docker image
- **THEN** the entrypoint clears any stale cache artifacts
- **AND** generates fresh configuration cache with current environment variables

### Requirement: Environment variable precedence
Docker environment variables MUST take precedence over values in `.env` files to follow standard Docker configuration patterns.

#### Scenario: Critical environment variables are set in Docker
- **WHEN** critical environment variables (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD) are set in the Docker environment
- **THEN** those values are used by the application
- **AND** values in `.env` files do not override them

#### Scenario: Environment variables are not set
- **WHEN** critical environment variables are not set in the Docker environment
- **THEN** the application uses default values from `.env.example.docker`
- **AND** the defaults are appropriate for the standard docker-compose deployment

### Requirement: Correct default database hostname
The `.env.example.docker` file MUST have `DB_HOST=flowarr-postgres` to match the docker-compose service name.

#### Scenario: New deployment uses example configuration
- **WHEN** a new deployment is created using `.env.example.docker` as the template
- **THEN** the `DB_HOST` value is `flowarr-postgres`
- **AND** the application can resolve the database hostname in the Docker network
