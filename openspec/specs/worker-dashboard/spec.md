# Worker Dashboard

## Purpose

Show real-time worker status on the dashboard so users can see what's running and identify idle or stuck workers.

## Requirements

### Requirement: Worker Status Cards
The dashboard SHALL display status cards for each worker or processing slot.

#### Scenario: Active workers shown
- **WHEN** a user views the dashboard
- **THEN** they see cards showing currently processing executions grouped by job type
- **THEN** each card shows job type, file being processed, and duration

#### Scenario: No active workers
- **WHEN** no executions are currently processing
- **THEN** the dashboard shows "No active workers" with a count of queued jobs
