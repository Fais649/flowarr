## Why

The system has multiple gaps preventing a working end-to-end pipeline. Queue workers don't process jobs because they only listen to the `default` queue while jobs are dispatched to `transcode`/`subtitle` queues. The dashboard lacks worker visibility, executions can't be cancelled, some libraries get stuck in `pending_scan`, and there's no easy way to test the pipeline end-to-end with realistic media files.

## What Changes

- **Worker queue fix**: `artisan dev` queue worker listens to all job queues (`transcode,subtitle,default`) instead of just `default`
- **Dashboard worker widgets**: Show per-worker status, active jobs, and health indicators
- **Execution cancellation**: Cancel/abort buttons on executions table with backend route to update status to `stopped`
- **Stuck pending_scan fix**: Investigate and fix why `dueForScan` doesn't pick up some `PENDING_SCAN` libraries
- **Test data setup**: Create `../test-data/` folder with sample `.mkv` and subtitle files, a `.bak` backup file, and `restore-test-data.sh` script
- **Debug restore button**: UI button (visible in debug/dev mode only) to trigger `restore-test-data.sh`

## Capabilities

### New Capabilities
- `worker-dashboard`: Worker status widgets on the dashboard showing per-worker state, active job count, health
- `execution-cancellation`: Cancel/abort executions from the table UI
- `test-data-management`: Test data folder with restore script for pipeline testing
- `debug-tools`: Debug-only button to trigger test data restore

### Modified Capabilities
- `queue-infrastructure`: Queue worker config changed to listen to all job queues; dev command options updated
- `scanning`: Fix for libraries stuck in `pending_scan` not being picked up
- `dashboard`: Dashboard enhanced with worker status widgets (requirement change from empty shell to populated dashboard)

## Impact

- `composer.json` or `config/dev.php` — queue worker args to listen on all queues
- `app/Http/Controllers/DashboardController.php` — add worker data to dashboard props
- `resources/js/pages/dashboard.tsx` — add worker status widgets
- `resources/js/pages/executions/index.tsx` — add cancel/abort button columns
- `routes/web.php` — add execution cancel route
- `app/Http/Controllers/ExecutionsController.php` — add cancel method
- `app/Models/Library.php` — fix `dueForScan` scope if needed
- `../test-data/` — new folder with sample media files, restore script
- `resources/js/pages/config/scan.tsx` or dashboard — debug restore button
