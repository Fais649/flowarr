# Library Management

## Purpose

Manage filesystem directories (libraries) that the system watches for media files, with configurable scan intervals and status lifecycle.

## Requirements

### Requirement: Library Schema
The system SHALL store libraries with a base path, status, scan interval, and last scan timestamp.

#### Scenario: Create a library
- **WHEN** a library record is created with a base_path
- **THEN** it defaults to pending status

### Requirement: Library Status Lifecycle
Libraries SHALL transition between statuses: pending, pending_scan, scanning, paused, stopped.

#### Scenario: Due for scan filtering
- **WHEN** querying libraries due for scan
- **THEN** only libraries with status pending_scan and elapsed time >= scan_interval are returned
- **THEN** only libraries with at least one configured LibraryJob are returned

### Requirement: Library CRUD
Users SHALL create, view, edit, and delete libraries through the web UI.

#### Scenario: List libraries
- **WHEN** a user visits /libraries
- **THEN** all libraries are displayed in a table with path, status, scan interval, last scan, and enabled jobs

#### Scenario: Create library
- **WHEN** a user submits the create library form with a base path and scan interval
- **THEN** a new library is created with status "pending"
- **WHEN** the base path is invalid, missing, or does not exist on disk
- **THEN** validation errors are displayed
- **WHEN** a user clicks "Browse" next to the base path input
- **THEN** a directory tree dialog opens for path selection

#### Scenario: Edit library
- **WHEN** a user updates a library's base path or scan interval
- **THEN** the changes are persisted
- **WHEN** the updated base path does not exist on disk
- **THEN** validation errors are displayed

#### Scenario: Delete library
- **WHEN** a user deletes a library
- **THEN** the library, its LibraryJobs, and all associated Executions are removed

### Requirement: Manual Scan Trigger
Users SHALL trigger a scan on a library from the UI.

#### Scenario: Trigger scan
- **WHEN** a user clicks "Scan Now" on a library
- **THEN** the library status changes to "pending_scan"
- **THEN** the scan command runs on the next scheduler tick

### Requirement: Library Detail Page
Users SHALL view library details including enabled jobs and recent executions.

#### Scenario: View library detail
- **WHEN** a user visits /libraries/{id}
- **THEN** the library path, status, and scan interval are displayed
- **THEN** enabled jobs are shown with toggle controls
- **THEN** recent executions for this library are listed

### Requirement: Scan Interval
Libraries SHALL have a configurable scan interval in seconds.

#### Scenario: Scan timing
- **WHEN** a library has never been scanned
- **THEN** it is immediately due for scan
- **WHEN** last_scan + scan_interval < current time
- **THEN** the library is due for scan again
