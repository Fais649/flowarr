# Scanning

## Purpose

Walk library filesystems, probe media files, and dispatch appropriate jobs based on file type and current encoding.

## Requirements

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

### Requirement: File Probing
Each discovered file SHALL be probed to determine its type and properties.

#### Scenario: Probe a file
- **WHEN** a file is encountered during scan
- **THEN** MediaProbeService determines its extension, video codec, and subtitle presence

### Requirement: Job Dispatch Decision
The system SHALL determine which jobs are needed for each file based on probe results and enabled jobs, and create `Execution` records.

#### Scenario: Transcode decision
- **WHEN** a file is a video and not already HEVC encoded
- **THEN** and the library has TRANSCODE_MEDIA enabled
- **THEN** an Execution is created with status `queued` linked to the library's `TRANSCODE_MEDIA` job
- **THEN** the execution ID SHALL be passed to the job as the identifier

#### Scenario: Subtitle extraction decision
- **WHEN** a video file has embedded subtitles
- **THEN** and the library has EXTRACT_SUBTITLES enabled
- **THEN** an Execution is created with status `queued` linked to the library's `EXTRACT_SUBTITLES` job

#### Scenario: Subtitle conversion decision
- **WHEN** a subtitle file is not already .srt
- **THEN** and the library has CONVERT_SUBTITLE enabled
- **THEN** an Execution is created with status `queued` linked to the library's `CONVERT_SUBTITLE` job

### Requirement: Dedup
The system SHALL skip files that already have pending or in-progress executions.

#### Scenario: Skip queued file
- **WHEN** a file already has a queued or processing execution for the same job
- **THEN** no duplicate execution is created

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

### Requirement: Libraries in PENDING_SCAN Are Picked Up
The `dueForScan` scope SHALL reliably pick up all libraries with status `PENDING_SCAN` that have enabled jobs.

#### Scenario: Library with PENDING_SCAN and no jobs
- **WHEN** a library has status `PENDING_SCAN` but no `libraryJobs` records
- **THEN** it SHALL be excluded from `dueForScan` (unchanged)
- **THEN** the logs SHALL indicate why the library was skipped

#### Scenario: Library with PENDING_SCAN and jobs
- **WHEN** a library has status `PENDING_SCAN` and at least one `libraryJob`
- **THEN** it SHALL be picked up by `dueForScan` regardless of `last_scan`
- **THEN** `scan:libraries` SHALL process it on the next tick
