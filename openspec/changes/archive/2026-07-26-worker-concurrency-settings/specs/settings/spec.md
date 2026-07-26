## ADDED Requirements

### Requirement: Worker Settings
The system SHALL provide a settings page for configuring per-job-type concurrency limits.

#### Scenario: Navigate to worker settings
- **WHEN** a user navigates to /settings/workers
- **THEN** a page is displayed with settings for each job type
- **THEN** each setting has a label, description, and number input
- **WHEN** the user updates a value and submits
- **THEN** the new limit is persisted and reflected immediately
