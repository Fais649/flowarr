## 1. Database Migration

- [x] 1.1 Create a new migration that adds a foreign key constraint on `executions.library_job_id` referencing `library_jobs.id` with `cascadeOnDelete()`
- [x] 1.2 Add a cleanup step in the migration that deletes any existing orphaned execution rows before adding the FK constraint (to prevent migration failure on existing data)
- [x] 1.3 Add the down migration that drops the foreign key constraint

## 2. Verification

- [x] 2.1 Run the migration and verify it applies cleanly
- [x] 2.2 Run `vendor/bin/sail artisan test --compact --filter=LibrariesControllerTest` to confirm existing library tests pass
- [x] 2.3 Create a manual test scenario: create library with jobs → create executions → delete library → verify executions are deleted
- [x] 2.4 Run `vendor/bin/sail bin pint` to format PHP
