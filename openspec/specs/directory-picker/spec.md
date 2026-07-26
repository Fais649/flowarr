# Directory Picker

## Purpose

Provide a server-side directory tree browser for selecting library base paths within the container filesystem.

## Requirements

### Requirement: Directory Listing Endpoint
The system SHALL provide an API endpoint that returns child directories for a given parent path on the container filesystem.

#### Scenario: List root directories
- **WHEN** a GET request is made to `/libraries/directories?path=/`
- **THEN** the response SHALL contain an array of first-level subdirectories
- **THEN** each entry SHALL include the directory name and full path
- **THEN** non-directories SHALL be excluded

#### Scenario: List subdirectories
- **WHEN** a GET request is made to `/libraries/directories?path=/media`
- **THEN** the response SHALL contain subdirectories within `/media`
- **THEN** the response SHALL include the current path for context

#### Scenario: Invalid path
- **WHEN** a GET request is made with a non-existent path
- **THEN** the response SHALL return a 404 error

#### Scenario: Path traversal blocked
- **WHEN** a GET request contains `..` or other path traversal patterns
- **THEN** the response SHALL return a 422 validation error
- **THEN** no filesystem access SHALL occur

### Requirement: Directory Browser UI
The library create/edit form SHALL include a "Browse" button that opens a directory tree dialog.

#### Scenario: Browse button visible
- **WHEN** viewing the library create or edit page
- **THEN** a "Browse" button SHALL be displayed next to the base path input field

#### Scenario: Open directory browser
- **WHEN** the user clicks "Browse"
- **THEN** a dialog SHALL open showing a directory tree
- **THEN** the tree SHALL start at the filesystem root (`/`)
- **THEN** directories SHALL be expandable without additional network requests

#### Scenario: Select directory
- **WHEN** the user clicks a directory in the tree
- **THEN** the directory name SHALL be highlighted
- **WHEN** the user confirms the selection
- **THEN** the dialog SHALL close
- **THEN** the chosen path SHALL populate the base path input field

#### Scenario: Navigate tree
- **WHEN** the user clicks an expand arrow on a directory
- **THEN** the directory SHALL expand to show its children immediately from the pre-fetched tree data
- **THEN** no additional network request SHALL be made when expanding a directory within the initial fetch depth
- **THEN** empty directories SHALL show no expand arrow

#### Scenario: Bulk tree fetch on dialog open
- **WHEN** the directory browser dialog opens
- **THEN** a single request SHALL fetch up to 5 levels of subdirectories starting from the root
- **THEN** the dialog SHALL show a loading indicator while the tree is being fetched
- **THEN** once loaded, all directories within the depth limit SHALL be expandable without further network requests

### Requirement: Root directory pre-loading
The directory browser SHALL pre-load the root directory contents when the dialog opens.

#### Scenario: Root pre-loaded on dialog open
- **WHEN** the directory browser dialog opens
- **THEN** the root directory SHALL automatically fetch and display its child directories
- **THEN** the user SHALL NOT need to click the expand arrow to load the root directory

### Requirement: Filesystem Validation on Submit
The library create and update form requests SHALL validate that the provided `base_path` exists and is a readable directory on disk.

#### Scenario: Valid path
- **WHEN** a library is created with a base_path that exists and is a directory
- **THEN** the request SHALL pass validation

#### Scenario: Non-existent path
- **WHEN** a library is created with a base_path that does not exist
- **THEN** the request SHALL fail validation
- **THEN** an error message SHALL indicate the directory does not exist

#### Scenario: Path is a file
- **WHEN** a library is created with a base_path that points to a file
- **THEN** the request SHALL fail validation
- **THEN** an error message SHALL indicate the path must be a directory

#### Scenario: Path not readable
- **WHEN** a library is created with a base_path that is not readable by the web server
- **THEN** the request SHALL fail validation
- **THEN** an error message SHALL indicate the directory is not accessible
