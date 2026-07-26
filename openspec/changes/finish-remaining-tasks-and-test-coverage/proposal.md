## Why

Multiple in-progress changes have lingering tasks that never got finished: 3 missing feature tests from production-readiness, 5 verification tasks from scan-file-filtering, and uncovered controllers (ScanSettings, WorkerSettings) without test coverage. Close out these loose ends so all changes can be archived cleanly.

## What Changes

- Add feature tests for settings profile controller, security controller, and auth flows (production-readiness tasks 9.2-9.4)
- Add test coverage for ScanSettingsController and WorkerSettingsController
- Run scan cleanup command and verify no stale QUEUED records
- Verify scan:libraries no longer picks up non-media files (.d.ts, etc.)
- Verify subtitle conversion only triggers for actual subtitle files
- Verify queue:work processes jobs on transcode/subtitle queues
- Close and archive both in-progress changes

## Capabilities

### New Capabilities

<!-- No new capabilities — all testing and verification of existing features -->

### Modified Capabilities
- `settings`: Add test coverage for settings profile and security controllers
- `scan-concurrency`: Add test coverage for ScanSettingsController
- `worker-concurrency`: Add test coverage for WorkerSettingsController

## Impact

- `tests/Feature/Settings/ProfileControllerTest.php` — new test file
- `tests/Feature/Settings/SecurityControllerTest.php` — new test file
- `tests/Feature/Auth/` — auth flow tests
- `tests/Feature/ScanSettingsControllerTest.php` — new test file
- `tests/Feature/WorkerSettingsControllerTest.php` — new test file
- `openspec/changes/production-readiness/` — final tasks completed, then archived
- `openspec/changes/fix-scan-file-filtering-and-queue-processing/` — verification tasks completed, then archived
