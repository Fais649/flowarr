## Context

Deleting a library currently orphans execution records. The `executions` table has `library_job_id` as a plain integer column with no foreign key constraint. The `LibrariesController::destroy()` deletes library jobs and the library but never touches executions.

## Goals / Non-Goals

**Goals:**
- Executions are deleted when their parent library is deleted
- Database-enforced referential integrity between executions and library_jobs
- Zero orphaned execution records

**Non-Goals:**
- Soft deletes (executions are hard-deleted on cascade)
- Preserving execution history after library deletion
- Changes to the execution listing or filtering logic

## Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Cascade mechanism | Database FK with `cascadeOnDelete()` | Guarantees no orphans regardless of how the library_job is deleted. One migration, no controller changes needed — the existing `$library->libraryJobs()->delete()` already triggers the cascade. |
| Existing rows | New migration adds FK; existing rows without FKs sail through | PostgreSQL will validate existing data when the FK is added. If any orphaned executions exist, the migration will fail — which is correct behavior (we want to detect and clean them up). |
| Controller change | None needed | The cascade handles it. `$library->libraryJobs()->delete()` cascades to executions. No code change in `LibrariesController`. |

## Risks / Trade-offs

- **Migration may fail on existing orphaned data** — If any execution rows have a `library_job_id` that doesn't exist in `library_jobs`, the FK addition will fail. Mitigation: add a cleanup step in the migration that deletes orphaned executions before adding the constraint, or require the user to run a cleanup command first.
