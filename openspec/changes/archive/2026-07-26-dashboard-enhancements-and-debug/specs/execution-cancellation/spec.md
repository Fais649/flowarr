# Execution Cancellation

## Purpose

Allow users to cancel queued executions or abort in-progress executions from the UI.

## Requirements

### Requirement: Cancel Execution Endpoint
The system SHALL provide a POST endpoint to cancel an execution.

#### Scenario: Cancel queued execution
- **WHEN** a user clicks cancel on a queued execution
- **THEN** a POST request is sent to `/executions/{id}/cancel`
- **THEN** the execution status SHALL be set to `stopped`
- **THEN** the table updates to show the new status

#### Scenario: Abort in-progress execution
- **WHEN** a user clicks abort on a processing execution
- **THEN** the execution status SHALL be set to `stopped`
- **THEN** the running job checks this status and self-terminates at the next check point

### Requirement: Cancel Button in Table
The executions table SHALL show cancel/abort buttons for actionable statuses.

#### Scenario: Button visibility
- **WHEN** an execution has status `queued`
- **THEN** a cancel button SHALL be shown
- **WHEN** an execution has status `processing`
- **THEN** an abort button SHALL be shown
- **WHEN** an execution has status `completed`, `failed`, or `stopped`
- **THEN** no cancel/abort button SHALL be shown
