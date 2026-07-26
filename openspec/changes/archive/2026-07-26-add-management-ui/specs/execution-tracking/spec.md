## ADDED Requirements

### Requirement: Execution Listing
Users SHALL view a paginated list of all executions with filtering.

#### Scenario: List executions
- **WHEN** a user visits /executions
- **THEN** executions are displayed in a paginated table with file path, library, job type, status, and timestamps

#### Scenario: Filter by status
- **WHEN** a user selects a status filter
- **THEN** only executions with that status are shown

#### Scenario: Filter by library
- **WHEN** a user selects a library filter
- **THEN** only executions for that library are shown

### Requirement: Execution Retry and Cancel
Users SHALL retry failed executions or cancel queued/pending executions.

#### Scenario: Retry failed execution
- **WHEN** a user clicks retry on a failed execution
- **THEN** a new execution is created with status "queued" for the same file and job

#### Scenario: Cancel queued execution
- **WHEN** a user clicks cancel on a queued execution
- **THEN** the execution status changes to "stopped"
