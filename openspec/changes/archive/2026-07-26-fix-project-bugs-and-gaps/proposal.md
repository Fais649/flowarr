## Why

The initial codebase has several bugs, missing pieces, and inconsistencies that need to be resolved before building on top of it. Leaving these gaps risks data loss (ConvertSubtitleJob deleting source on failure), broken test data (factories generating invalid states), and confusing project structure.

## What Changes

- Fix `ConvertSubtitleJob` to not delete source files on conversion failure
- Create missing `DispatchableJob` interface that all job classes implement
- Fix `LibraryFactory` and `ExecutionFactory` to generate valid enum values
- Create `Worker` model for the existing `workers` table migration
- Move nested `TranscodeMediaJobTest` to the correct test directory
- Add `ffmpeg` and `mkvmerge` configuration keys to `config/services.php`
- Replace Symfony `now()` import in `ScanLibraryCommand` with Laravel helper

## Capabilities

### New Capabilities

None — all changes are fixes to existing capabilities.

### Modified Capabilities

- `subtitle-conversion`: Error handling requirement — source file must not be deleted on failure
- `queue-infrastructure`: Job dispatch requirement — all jobs must implement `DispatchableJob` interface

## Impact

- `app/Jobs/ConvertSubtitleJob.php` — fix finally-block behavior
- `app/Jobs/` — create `DispatchableJob` interface, implement on all job classes
- `app/Models/` — create `Worker` model
- `app/Console/Commands/ScanLibraryCommand.php` — fix import
- `config/services.php` — add ffmpeg and mkvmerge config keys
- `database/factories/LibraryFactory.php` — fix status and scan_interval values
- `database/factories/ExecutionFactory.php` — fix status values
- `tests/Feature/tests/Feature/TranscodeMediaJobTest.php` — move to correct location
- `database/migrations/2026_07_04_193427_create_worker_table.php` — rename `createdAt` to `created_at`
