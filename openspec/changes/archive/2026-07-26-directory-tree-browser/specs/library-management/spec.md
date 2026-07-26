## MODIFIED Requirements

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
- **THEN** the library and its associated LibraryJobs are removed
- **THEN** associated Executions are preserved
