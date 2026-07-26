## 1. Fix dueForScan Scope

- [x] 1.1 Update `dueForScan` scope in `app/Models/Library.php` — add `PENDING` to the `whereIn('status', [...])` so both `PENDING` and `PENDING_SCAN` libraries are eligible
- [x] 1.2 Verify `PENDING` libraries with expired interval are picked up by `scan:libraries` — run the command and check library is scanned

## 2. Fix Initial Library Status

- [x] 2.1 Change `LibrariesController::store` — set initial status to `PENDING_SCAN` instead of `PENDING`
- [x] 2.2 Update any tests that assert initial status is `PENDING` (none found — no changes needed)

## 3. Test Coverage

- [x] 3.1 Add test to `ScanLibrariesCommandTest`: create library with `PENDING` status + past `last_scan` time — assert it gets picked up by `dueForScan`
- [x] 3.2 Add test: create library with `PENDING` status + recent `last_scan` — assert it's NOT picked up (interval not elapsed)
- [x] 3.3 Add test: create library with `PENDING_SCAN` status — assert it IS picked up regardless of interval (manual trigger path preserved)
