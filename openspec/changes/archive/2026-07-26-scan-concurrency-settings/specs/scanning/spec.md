## MODIFIED Requirements

### Requirement: Scheduled Scan Command
The system SHALL provide an Artisan command that scans all libraries due for scan and dispatches jobs.

#### Scenario: ScanLibraries command exists
- **WHEN** running `php artisan scan:libraries`
- **THEN** it SHALL query all libraries where `status` is not paused/stopped and `(last_scan + scan_interval) < now()`
- **THEN** libraries SHALL be scanned concurrently up to the configured `scan.concurrency` limit
- **THEN** each library SHALL be scanned for media files
- **THEN** library status SHALL be set to `scanning` during scan and restored to `pending` after

#### Scenario: Default scan interval
- **WHEN** a new library is created
- **THEN** its default `scan_interval` SHALL be 43200 seconds (12 hours)

### Requirement: Scan Concurrency Limit
The system SHALL limit how many libraries scan simultaneously.

#### Scenario: At the limit
- **WHEN** the number of currently scanning libraries is at or above `scan.concurrency`
- **THEN** additional libraries wait until a slot opens
