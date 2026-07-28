## ADDED Requirements

### Requirement: Production stack starts
The e2e test SHALL start the production Docker stack using `docker compose up -d`.

#### Scenario: Stack starts successfully
- **WHEN** the e2e test begins
- **THEN** it SHALL run `docker compose -f docker-compose.yml up -d` with a test override for port mapping
- **THEN** all three services (postgres, redis, app) SHALL report healthy status
- **THEN** the web app SHALL respond to HTTP requests on the mapped port

### Requirement: Test media is mounted
The e2e test SHALL mount a directory of test media files into the container at `/media`.

#### Scenario: Test media exists
- **WHEN** the stack starts
- **THEN** test media files SHALL be mounted at `/media` inside the flowarr container
- **THEN** the mount SHALL include at least one video file and one subtitle file

#### Scenario: Test media does not exist
- **WHEN** the test media directory does not exist
- **THEN** the skill SHALL create synthetic test files using `ffmpeg`
- **THEN** the synthetic files SHALL be placed in the mount path

### Requirement: Library is created and scanned
The e2e test SHALL create a library for the test media directory and trigger a scan.

#### Scenario: Library creation and scan
- **WHEN** the stack is healthy
- **THEN** the skill SHALL create a library via the web API pointing to `/media`
- **THEN** the skill SHALL trigger a scan of the library
- **THEN** the scan SHALL queue jobs for the media files

### Requirement: Jobs are processed
The e2e test SHALL run the queue worker to process all queued jobs.

#### Scenario: Queue worker processes jobs
- **WHEN** jobs are queued
- **THEN** the skill SHALL run `php artisan queue:work --queue=transcode,subtitle --sleep=3 --tries=1 --max-jobs=10 --max-time=120`
- **THEN** all jobs SHALL reach a terminal status (completed or failed)

### Requirement: Results are verified
The e2e test SHALL verify that all executions reached a terminal state.

#### Scenario: All jobs completed
- **WHEN** the queue worker finishes
- **THEN** the skill SHALL query the database for executions linked to the test library
- **THEN** all executions SHALL have status `completed` or `failed`
- **THEN** no executions SHALL be stuck in `queued` or `processing`

### Requirement: Cleanup
The e2e test SHALL tear down the stack after completion regardless of pass or fail.

#### Scenario: Stack torn down
- **WHEN** the e2e test finishes (pass or fail)
- **THEN** the skill SHALL run `docker compose -f docker-compose.yml down -v`
- **THEN** all containers SHALL be removed
