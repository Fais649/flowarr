## Context

The codebase was built from a Laravel starter kit with Blueprint-generated models and custom job classes. Several issues were introduced during initial scaffolding that need correction before further development. Each fix is small and self-contained.

## Goals / Non-Goals

**Goals:**
- Prevent data loss in ConvertSubtitleJob when ffmpeg fails
- Formalize the job contract with a shared interface
- Generate valid test data from factories
- Close the gap between the workers migration and its model
- Fix project structure (nested test directory)
- Add missing configuration defaults
- Use consistent Laravel helpers

**Non-Goals:**
- Job ↔ Execution integration (Phase 1 in ARCHITECTURE.md — separate change)
- Queue routing configuration
- New features or capabilities

## Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| ConvertSubtitleJob fix | Move `unlink` from `finally` to success path only | Simplest fix with no architectural change. Error is already re-thrown — the finally block should only clean up temp files, not the source |
| DispatchableJob interface | Add `app/Jobs/Contracts/DispatchableJob.php` with `handle(): void` | Matches the existing reference in LibraryJobId. Provides a typed contract without changing Laravel's job dispatch mechanism |
| Worker model | Create `app/Models/Worker.php` with standard Laravel conventions | Fixes missing model for existing migration. Rename `createdAt` → `created_at` in migration to follow Laravel convention |
| Factory data | Use `LibraryStatus::cases()` and `ExecutionStatus::cases()` with `fake()->randomElement()` | Guarantees valid enum values. Keeps factories useful for tests |
| Symfony `now()` | Replace with Laravel's `now()` helper | Consistency. The Symfony clock import is an unnecessary dependency |

## Risks / Trade-offs

- **DispatchableJob interface is purely documentary** — PHP doesn't enforce interface implementation at dispatch time. The value is in IDE support and documentation, not runtime safety. Acceptable — same pattern as contract interfaces throughout Laravel.
- **Worker migration rename** — If the database has already run the migration in any environment, the rename requires a new migration. Safe for local dev; document for existing deployments.
- **ConvertSubtitleJob fix is small** — The change is moving three lines. Low risk, high impact (prevents data loss).
