## Why

Deleting a library currently orphans its executions — the `library_jobs` are deleted but the `executions` records remain with a `library_job_id` pointing to nothing. This leaves the UI showing phantom executions that can never be cleaned up through normal workflows.

## What Changes

- When a library is deleted, all its associated executions are also deleted
- Add a foreign key constraint on `executions.library_job_id` with cascade-on-delete to guarantee referential integrity at the database level

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `library-management`: Delete library scenario updated — executions are now deleted instead of preserved
- `execution-tracking`: Execution model gains foreign key constraint to library_jobs with cascade delete

## Impact

- `app/Http/Controllers/LibrariesController.php` — add `executions` delete in destroy method (or rely on DB cascade)
- `database/migrations/` — new migration to add FK constraint with `cascadeOnDelete` on `executions.library_job_id`
- `database/migrations/2026_07_05_084406_create_executions_table.php` — no change; new migration for the FK
