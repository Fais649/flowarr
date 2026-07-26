## MODIFIED Requirements

### Requirement: Scheduled Scan Command
The system SHALL provide an Artisan command that scans all libraries due for scan and dispatches jobs.

#### Scenario: ScanLibraries command exists
- **WHEN** running `php artisan scan:libraries`
- **THEN** it SHALL query all libraries where `status` is not paused/stopped and `(last_scan + scan_interval) < now()`
- **THEN** each library SHALL be scanned for media files
- **THEN** library status SHALL be set to `scanning` during scan and restored to `pending` after

#### Scenario: Command scheduled
- **WHEN** the scheduler runs
- **THEN** the `scan:libraries` command SHALL be registered to run every minute

### Requirement: Execution Creation During Scan
The scanner SHALL create `Execution` records for files requiring processing and dispatch the corresponding queue job.

#### Scenario: Execution created
- **WHEN** a file requires transcoding
- **THEN** an `Execution` is created with status `queued` linked to the library's `TRANSCODE_MEDIA` job
- **THEN** the execution ID SHALL be passed to the job as the identifier

#### Scenario: Execution created for subtitle extraction
- **WHEN** a file has embedded subtitles and extraction is enabled
- **THEN** an `Execution` is created with status `queued` linked to the library's `EXTRACT_SUBTITLES` job

#### Scenario: Execution created for subtitle conversion
- **WHEN** a non-SRT subtitle sidecar file is found and conversion is enabled
- **THEN** an `Execution` is created with status `queued` linked to the library's `CONVERT_SUBTITLE` job

### Requirement: Execution Status Updates in Jobs
Queue jobs SHALL update their corresponding `Execution` record as they progress through their lifecycle.

#### Scenario: Job starts processing
- **WHEN** a job begins its `handle()` method
- **THEN** the corresponding Execution status is updated to `processing`
- **THEN** the `started_at` timestamp is set

#### Scenario: Job completes successfully
- **WHEN** a job finishes without error
- **THEN** the corresponding Execution status is updated to `completed`
- **THEN** the `finished_at` timestamp is set

#### Scenario: Job fails
- **WHEN** a job throws an exception
- **THEN** the corresponding Execution status is updated to `failed`
- **THEN** the `finished_at` timestamp is set
- **THEN** the exception message SHALL be logged alongside the execution
