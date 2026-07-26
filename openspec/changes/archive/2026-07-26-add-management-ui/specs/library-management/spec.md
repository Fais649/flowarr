## ADDED Requirements

### Requirement: Library CRUD
Users SHALL create, view, edit, and delete libraries through the web UI.

#### Scenario: List libraries
- **WHEN** a user visits /libraries
- **THEN** all libraries are displayed in a table with path, status, scan interval, last scan, and enabled jobs

#### Scenario: Create library
- **WHEN** a user submits the create library form with a base path and scan interval
- **THEN** a new library is created with status "pending"
- **WHEN** the base path is invalid or missing
- **THEN** validation errors are displayed

#### Scenario: Edit library
- **WHEN** a user updates a library's base path or scan interval
- **THEN** the changes are persisted

#### Scenario: Delete library
- **WHEN** a user deletes a library
- **THEN** the library and its associated LibraryJobs are removed
- **THEN** associated Executions are preserved

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
