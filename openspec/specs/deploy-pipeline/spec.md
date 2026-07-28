# Deploy Pipeline

## Purpose

Automate building, testing, and publishing the production Docker image to Docker Hub, with push to GitHub.

## Requirements

### Requirement: Deploy runs test suite first
The deploy pipeline SHALL run the full test suite as the first step.

#### Scenario: Tests pass
- **WHEN** the deploy pipeline starts
- **THEN** it SHALL run `vendor/bin/sail artisan test --compact`
- **THEN** if all tests pass, the pipeline SHALL proceed to the next step

#### Scenario: Tests fail
- **WHEN** any test fails
- **THEN** the pipeline SHALL diagnose the failure
- **THEN** the pipeline SHALL fix the issue
- **THEN** the pipeline SHALL re-run the tests
- **THEN** this loop SHALL repeat until all tests pass

### Requirement: Frontend assets build
The deploy pipeline SHALL build frontend assets before building the Docker image.

#### Scenario: Build succeeds
- **WHEN** tests pass
- **THEN** the pipeline SHALL run `vendor/bin/sail bun run build`
- **THEN** if the build succeeds, the pipeline SHALL proceed

#### Scenario: Build fails
- **WHEN** the frontend build fails
- **THEN** the pipeline SHALL fix the build error
- **THEN** the pipeline SHALL retry the build

### Requirement: Docker image build
The deploy pipeline SHALL build the production Docker image.

#### Scenario: Docker build succeeds
- **WHEN** frontend assets are built
- **THEN** the pipeline SHALL run `docker build -t fais649/flowarr:latest .`
- **THEN** if the build succeeds, the pipeline SHALL proceed

#### Scenario: Docker build fails
- **WHEN** the Docker build fails
- **THEN** the pipeline SHALL fix the issue
- **THEN** the pipeline SHALL retry the build

### Requirement: Push to GitHub
The deploy pipeline SHALL commit all changes and push to the remote repository.

#### Scenario: Git push succeeds
- **WHEN** the Docker image is built
- **THEN** the pipeline SHALL run `git add -A && git commit -m "<message>" && git push`
- **THEN** if successful, the pipeline SHALL proceed

#### Scenario: Git push fails
- **WHEN** git push fails
- **THEN** the pipeline SHALL fix the issue (merge conflicts, auth, etc.)
- **THEN** the pipeline SHALL retry the push

### Requirement: Push to Docker Hub
The deploy pipeline SHALL push the built image to Docker Hub.

#### Scenario: Docker push succeeds
- **WHEN** git push succeeds
- **THEN** the pipeline SHALL run `docker push fais649/flowarr:latest`
- **THEN** the pipeline SHALL report deploy complete

#### Scenario: Docker push fails
- **WHEN** docker push fails
- **THEN** the pipeline SHALL retry after diagnosing the issue
