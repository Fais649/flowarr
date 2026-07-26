## ADDED Requirements

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
