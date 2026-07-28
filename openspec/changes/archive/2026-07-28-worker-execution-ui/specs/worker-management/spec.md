## ADDED Requirements

### Requirement: Create worker with job type
Users SHALL be able to create a worker by selecting a job type and optionally setting a name and concurrency limit.

#### Scenario: Add Worker button opens creation form
- **WHEN** the user clicks "Add Worker" on the Workers list page
- **THEN** a form SHALL appear with fields for Name, Job Type (transcode media / extract subs / convert subs), and Concurrency
- **THEN** submitting the form SHALL create a new Worker record
- **THEN** the new worker SHALL appear in the workers list

### Requirement: Worker list shows lifecycle controls
Each worker in the list SHALL have buttons to Start, Pause, Resume, and Stop.

#### Scenario: Per-worker action buttons
- **WHEN** the user is viewing the workers list
- **THEN** each worker row SHALL have Start, Pause, Resume, and Stop buttons
- **THEN** buttons SHALL be disabled when the action is invalid for the current state

#### Scenario: Bulk actions on all workers
- **WHEN** the user clicks "Start All", "Pause All", "Resume All", or "Stop All"
- **THEN** the action SHALL be applied to all workers
- **THEN** a single POST request SHALL be sent per action type

### Requirement: Worker detail view
The worker detail page SHALL show worker info, lifecycle controls, and concurrency settings.

#### Scenario: Detail view with controls
- **WHEN** the user navigates to a worker detail page
- **THEN** the page SHALL display the worker name, job type, concurrency, creation date, and last heartbeat
- **THEN** the page SHALL have Start, Pause, Resume, and Stop buttons
- **THEN** the page SHALL have inputs to edit the worker's concurrency and name

### Requirement: Config workers tab removed
The separate config/workers settings page SHALL be removed. Its functionality SHALL be available within the Workers tab.

#### Scenario: Workers tab replaces config page
- **WHEN** the user navigates to the config section
- **THEN** the "Workers" settings page SHALL no longer be listed
- **WHEN** the user navigates to the Workers tab
- **THEN** the page SHALL include concurrency settings and global pause controls
