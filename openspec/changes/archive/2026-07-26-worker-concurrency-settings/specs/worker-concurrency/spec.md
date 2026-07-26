## ADDED Requirements

### Requirement: Settings Storage
The system SHALL store application settings in a `settings` database table with key-value pairs.

#### Scenario: Store a setting
- **WHEN** a setting key and value are saved
- **THEN** the value is persisted in the settings table
- **THEN** it can be retrieved by key

#### Scenario: Default values
- **WHEN** a setting has not been configured
- **THEN** the system returns a sensible default (e.g., 1 for transcode, 4 for subtitle)

### Requirement: Per-Job-Type Concurrency Limit
Each job type SHALL have a configurable maximum number of concurrent executions.

#### Scenario: Concurrency enforced
- **WHEN** a job starts and the number of currently processing executions for its job type is at or above the limit
- **THEN** the job releases itself back to the queue with a delay
- **THEN** no processing occurs

#### Scenario: Under the limit
- **WHEN** a job starts and the number of currently processing executions is below the limit
- **THEN** the job proceeds normally

#### Scenario: Paused jobs count toward limit
- **WHEN** a job is paused (SIGSTOP'd) due to active streams
- **THEN** it still counts toward the concurrency limit
- **THEN** no new jobs of that type start until processing count drops below the limit

### Requirement: Worker Settings UI
Authenticated users SHALL view and update concurrency limits per job type from a settings page.

#### Scenario: View worker settings
- **WHEN** a user visits /settings/workers
- **THEN** they see a list of job types with their current concurrency limits
- **THEN** each limit is displayed in an editable number input

#### Scenario: Update concurrency limit
- **WHEN** a user changes a concurrency limit and submits
- **THEN** the setting is persisted
- **THEN** the change takes effect immediately (next job pick-up uses the new limit)

#### Scenario: Validation
- **WHEN** a user enters a value less than 1
- **THEN** validation fails with an error message

### Requirement: Shared Pause Check
All jobs SHALL use the same `shouldPause()` logic and concurrency check pattern, either via a shared trait or base class.

#### Scenario: Consistent behavior
- **WHEN** any job checks whether it should pause
- **THEN** it checks `media_processing_paused` and `active_streams` cache keys
- **WHEN** any job checks whether it can proceed
- **THEN** it checks the concurrency limit for its job type against currently processing executions
