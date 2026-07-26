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

### Requirement: File Filtering
The scanner SHALL only process files with known media extensions. Non-media files SHALL be excluded before any probing or dispatch logic.

#### Scenario: Filter before probe
- **WHEN** the scanner iterates files in a library directory
- **THEN** it SHALL check the file extension against the shared media extension allowlist
- **THEN** if the extension is not in the allowlist, the file SHALL be skipped entirely

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
- **WHEN** a file is confirmed as a subtitle file and is not already `.srt`
- **THEN** and the library has CONVERT_SUBTITLE enabled
- **THEN** an Execution is created with status `queued` linked to the library's `CONVERT_SUBTITLE` job
- **WHEN** a file is not a subtitle file
- **THEN** it SHALL NOT be queued for subtitle conversion regardless of extension

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
