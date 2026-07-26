# Execution Tracking

## Purpose

Track the lifecycle of each individual job run — from queued through processing to completion or failure.

## Requirements

### Requirement: Execution Schema
Each execution SHALL record the library job, target file path, worker, status, and timestamps.

#### Scenario: Create execution
- **WHEN** a file is queued for processing
- **THEN** an Execution record is created with status "queued"

### Requirement: Status Lifecycle
Executions SHALL transition through statuses: queued → processing → completed | failed | stopped | paused.

#### Scenario: Processing starts
- **WHEN** a worker picks up a queued job
- **THEN** the execution status changes to "processing"
- **THEN** started_at is recorded

#### Scenario: Successful completion
- **WHEN** a job completes successfully
- **THEN** the execution status changes to "completed"
- **THEN** finished_at is recorded

#### Scenario: Job failure
- **WHEN** a job fails
- **THEN** the execution status changes to "failed"
- **THEN** finished_at is recorded

### Requirement: Dedup on Dispatch
The system SHALL avoid creating duplicate executions for the same file and job.

#### Scenario: Skip existing
- **WHEN** an execution already exists for a file+job with status queued or processing
- **THEN** no new execution is created

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

### Requirement: Referential Integrity
The `executions.library_job_id` column SHALL have a foreign key constraint referencing `library_jobs.id` with cascade-on-delete.

#### Scenario: Cascade delete on library job removal
- **WHEN** a library_job record is deleted
- **THEN** all executions referencing that library_job are automatically deleted

#### Scenario: Cascade delete via library deletion
- **WHEN** a library is deleted
- **THEN** its library_jobs are deleted
- **THEN** executions referencing those library_jobs are deleted via the FK cascade

#### Scenario: Orphan prevention
- **WHEN** a library_job is deleted through any mechanism
- **THEN** no execution records SHALL remain pointing to the deleted library_job

### Requirement: Execution Retry and Cancel
Users SHALL retry failed executions or cancel queued/pending executions.

#### Scenario: Retry failed execution
- **WHEN** a user clicks retry on a failed execution
- **THEN** a new execution is created with status "queued" for the same file and job

#### Scenario: Cancel queued execution
- **WHEN** a user clicks cancel on a queued execution
- **THEN** the execution status changes to "stopped"
