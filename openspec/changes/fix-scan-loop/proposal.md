## Why

Libraries are stuck in `PENDING` after their first scan. The scheduled `scan:libraries` command only picks up libraries with status `PENDING_SCAN`, but nothing ever transitions them back to `PENDING_SCAN` once a scan completes. The core automation loop (scan → process → wait → re-scan) is broken — libraries only scan once, either on creation or when the user manually triggers a scan via the UI.

## What Changes

- `dueForScan` scope on `Library` model: include `PENDING` libraries whose `last_scan + scan_interval` is due, not just `PENDING_SCAN`
- Library creation: set initial status to `PENDING_SCAN` so auto-scan picks it up immediately (instead of `PENDING` which requires manual trigger)
- Update `LibrariesController` tests to reflect the new initial status
- Add test coverage for recurring scan cycle: verify `PENDING` library is picked up after `scan_interval` elapses

## Capabilities

### New Capabilities
- `scanning`: fix the automated scanning loop so libraries re-scan on schedule without manual intervention

### Modified Capabilities

<!-- No existing capabilities change their spec-level behavior. The scanning capability already describes what scanning does — this change fixes the trigger mechanism to match the spec's intent. -->

## Impact

- `app/Models/Library.php` — change `dueForScan` scope to include `PENDING` status
- `app/Http/Controllers/LibrariesController.php` — change initial status on create from `PENDING` to `PENDING_SCAN`
- `tests/Feature/ScanLibrariesCommandTest.php` — add recurring scan cycle test
