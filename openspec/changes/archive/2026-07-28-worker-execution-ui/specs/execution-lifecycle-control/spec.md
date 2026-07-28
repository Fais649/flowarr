## ADDED Requirements

### Requirement: Manual start/pause/resume/stop for single execution
Users SHALL be able to manually start, pause, resume, or stop a single execution directly from the executions list or detail page, without waiting for queue processing.

#### Scenario: Start an execution
- **WHEN** the user clicks "Start" on a queued or paused execution
- **THEN** the execution status SHALL change to processing
- **THEN** the action SHALL take effect immediately, bypassing the queue

#### Scenario: Pause an execution
- **WHEN** the user clicks "Pause" on a processing execution
- **THEN** the execution status SHALL change to paused
- **THEN** the underlying job process SHALL be suspended

#### Scenario: Resume an execution
- **WHEN** the user clicks "Resume" on a paused execution
- **THEN** the execution status SHALL change back to processing
- **THEN** the underlying job process SHALL be resumed

#### Scenario: Stop an execution
- **WHEN** the user clicks "Stop" on a queued, processing, or paused execution
- **THEN** the execution status SHALL change to stopped

### Requirement: Batch lifecycle actions
Users SHALL be able to select multiple executions and apply start, pause, resume, or stop to all selected.

#### Scenario: Select multiple executions
- **WHEN** the user checks the checkbox next to multiple executions
- **THEN** a batch action toolbar SHALL appear
- **THEN** the user SHALL be able to click Start Selected, Pause Selected, Resume Selected, or Stop Selected

### Requirement: Delete executions
Users SHALL be able to delete one or more execution records from the executions list.

#### Scenario: Delete single execution
- **WHEN** the user clicks the delete button on an execution
- **THEN** a confirmation dialog SHALL appear
- **THEN** confirming SHALL permanently delete the execution record

#### Scenario: Delete multiple executions
- **WHEN** the user selects multiple executions and clicks "Delete Selected"
- **THEN** a confirmation dialog SHALL appear with the count of executions to delete
- **THEN** confirming SHALL permanently delete all selected execution records
