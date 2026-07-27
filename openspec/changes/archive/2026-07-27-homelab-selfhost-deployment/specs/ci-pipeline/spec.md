## ADDED Requirements

### Requirement: Docker image build and publish
The CI pipeline SHALL build and publish a production Docker image to GitHub Container Registry (GHCR) when a version tag is pushed.

#### Scenario: Tagged release builds and pushes
- **WHEN** a tag matching `v*` is pushed
- **THEN** the publish workflow SHALL build the Docker image using the repo root `Dockerfile`
- **THEN** the image SHALL be tagged with `:v<major>.<minor>.<patch>`, `:v<major>.<minor>`, and `:latest`
- **THEN** the image SHALL be pushed to `ghcr.io/<owner>/flowarr`

#### Scenario: Main branch builds edge tag
- **WHEN** code is pushed to the `main` branch
- **THEN** the publish workflow SHALL build the Docker image
- **THEN** the image SHALL be tagged with `:edge`
- **THEN** the image SHALL be pushed to `ghcr.io/<owner>/flowarr`

### Requirement: OCI metadata labels
Published images SHALL include OCI-compliant metadata labels for provenance tracking.

#### Scenario: Labels attached
- **WHEN** a Docker image is built and pushed
- **THEN** the image SHALL include `org.opencontainers.image.source`, `org.opencontainers.image.revision`, `org.opencontainers.image.version`, `org.opencontainers.image.title`, and `org.opencontainers.image.description` labels
