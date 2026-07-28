## ADDED Requirements

### Requirement: Production Dockerfile
The repository SHALL include a `Dockerfile` at the root directory that produces a production runtime image using multi-stage builds.

#### Scenario: Build completes successfully
- **WHEN** `docker build -t flowarr .` is run from the repo root
- **THEN** the build exits with code 0 and produces an image tagged `flowarr`

#### Scenario: Dev artifacts excluded from final image
- **WHEN** the image is built from the root Dockerfile
- **THEN** the final image SHALL NOT contain `node_modules/`, `.env`, `.git/`, `tests/`, or any `vendor/` dev packages

#### Scenario: PHP extensions present
- **WHEN** inspecting the runtime image
- **THEN** PHP extensions `pgsql`, `gd`, `bcmath`, `redis`, `mbstring`, `xml`, `zip`, `curl` SHALL be available in `php -m`

### Requirement: .dockerignore
The repository SHALL include a `.dockerignore` file that excludes dev and CI artifacts from the Docker build context.

#### Scenario: Build context excludes dev files
- **WHEN** running `docker build`
- **THEN** the build context SHALL exclude `node_modules`, `vendor`, `.git`, `tests`, `tests-frontend`, `tests-browser`, `.github`, `.storybook`, `stubs`, `docker/`, `openspec/`, `*.md`, and IDE config files

### Requirement: GitHub Actions publish workflow
The repository SHALL include a `.github/workflows/publish.yml` workflow that builds and publishes Docker images to GHCR.

#### Scenario: Version tag triggers release image
- **WHEN** a git tag matching `v*` is pushed
- **THEN** the workflow SHALL build the Docker image and push to `ghcr.io/fais/flowarr` with tags `:vX.Y.Z` (from tag name) and `:latest`

#### Scenario: Main branch trigger edge image
- **WHEN** a push to `main` occurs (not a tag push)
- **THEN** the workflow SHALL build the Docker image and push to `ghcr.io/fais/flowarr` with tag `:edge`

#### Scenario: Workflow uses GHCR authentication
- **WHEN** the workflow runs the push step
- **THEN** it SHALL authenticate to GHCR using `GITHUB_TOKEN` with `write:packages` permission

### Requirement: Versioned image labels
Published images SHALL include OCI-standard labels for provenance.

#### Scenario: Labels present on image
- **WHEN** inspecting the published image metadata
- **THEN** the image SHALL have labels `org.opencontainers.image.source` (repo URL), `org.opencontainers.image.revision` (commit SHA), `org.opencontainers.image.version` (tag or "edge"), and `org.opencontainers.image.title` ("Flowarr")

### Requirement: Production runtime environment
The image SHALL serve the Laravel application via Laravel Octane with Roadrunner.

#### Scenario: Default container starts Octane
- **WHEN** running the image with `docker run -p 80:80 flowarr`
- **THEN** the container SHALL start `php artisan octane:start --server=roadrunner --host=0.0.0.0 --port=80` serving the application on port 80

#### Scenario: Roadrunner binary available
- **WHEN** the container starts
- **THEN** the Roadrunner binary SHALL be present in `vendor/bin/rr` or at a known path

#### Scenario: Health probe responds
- **WHEN** curling `http://localhost/health` inside the container
- **THEN** the response SHALL be HTTP 200 with a JSON body containing `{"status": "ok"}`

### Requirement: README self-host quickstart
The `README.md` SHALL include a "Self-Hosting" section with Docker Compose instructions.

#### Scenario: Quickstart snippet present
- **WHEN** reading `README.md`
- **THEN** there SHALL be a code block showing a `docker-compose.yml` snippet with the `flowarr` service using `ghcr.io/fais/flowarr:latest`

#### Scenario: Required environment variables documented
- **WHEN** reading the self-hosting section
- **THEN** the required env vars (APP_KEY, DB_*, OCTANE_SERVER, etc.) and their purposes SHALL be documented
