## ADDED Requirements

### Requirement: Library detail shows worker selector
The library detail view SHALL allow users to select which workers are enabled for that library instead of toggling abstract job types.

#### Scenario: Worker checkboxes in library detail
- **WHEN** the user views a library detail page
- **THEN** the "Job Toggles" card SHALL be replaced with a list of all available workers
- **THEN** each worker SHALL have a toggle to enable or disable it for this library
- **THEN** toggling a worker SHALL associate or disassociate the worker with the library

#### Scenario: Worker filtering by job type
- **WHEN** the user selects a worker for a library
- **THEN** only workers with a matching job type SHALL be shown as available options
- **THEN** the worker's job type SHALL be displayed next to its name

### Requirement: Library detail shows worker job history
The library detail view SHALL display recent executions grouped by their associated worker.

#### Scenario: Worker column in recent executions
- **WHEN** the user views recent executions on a library detail page
- **THEN** each execution SHALL display the worker name (if assigned)
- **THEN** executions SHALL be filterable by worker
