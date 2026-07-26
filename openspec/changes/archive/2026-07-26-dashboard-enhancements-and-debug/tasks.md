## 1. Queue Worker Fix

- [x] 1.1 Update queue worker args — override default queue dev command via ArtisanStarting event to listen on `transcode,subtitle,default`

## 2. Dashboard Worker Widgets

- [x] 2.1 Add `processingExecutions` and `queuedByType` data to `DashboardController` props
- [x] 2.2 Create worker status section on dashboard showing currently processing jobs grouped by type
- [x] 2.3 Add queued-by-type badges in worker activity section

## 3. Execution Cancellation

- [x] 3.1 Cancel method already exists in `ExecutionsController`
- [x] 3.2 Cancel route already registered in `routes/web.php`
- [x] 3.3 Cancel button already wired in executions table
- [x] 3.4 Added contextual label: "Abort" for processing, "Cancel" for queued

## 4. Stuck PENDING_SCAN Fix

- [x] 4.1 Investigated — PENDING_SCAN with no libraryJobs filtered by `has('libraryJobs')`; stuck SCANNING reset added
- [x] 4.2 Added debug logging in ScanLibraries for PENDING_SCAN libraries without jobs
- [x] 4.3 Scope is correct — PENDING_SCAN unconditionally included; added stuck-SCANNING timeout reset

## 5. Test Data Setup

- [x] 5.1 Created `test-data/.bak/` with sample `.mkv` (10s h.264) and `.srt` files
- [x] 5.2 Created `restore-test-data.sh` — copies `.bak` files to `test-data/`, removes non-backup files
- [x] 5.3 Active files populated from `.bak` on creation

## 6. Debug Restore Button

- [x] 6.1 Created debug "Restore Test Data" button on /config/workers — visible when isLocal
- [x] 6.2 Created POST /debug/restore-test-data route with DebugController
