## Why

Scanning multiple libraries runs sequentially — one library at a time. With several libraries each taking minutes to scan, the backlog grows and libraries may not get scanned for hours. Users need control over how many libraries scan concurrently. Additionally, the default 1-hour scan interval is too aggressive for a media library that doesn't change often; reducing it reduces unnecessary I/O and log noise.

## What Changes

- Add `scan.concurrency` setting (default: 2) to control parallel library scans
- `ScanLibraries` command scans libraries concurrently up to the concurrency limit using `Concurrency::run()`
- Add a scan settings page in the UI (similar to existing worker settings) to configure concurrency
- Change default `scan_interval` on new libraries from 3600s (1 hour) to 43200s (12 hours)
- Add scan concurrency route + controller following the existing `WorkerSettingsController` pattern

## Capabilities

### New Capabilities
- `scan-concurrency`: Configurable setting for how many libraries scan in parallel

### Modified Capabilities
- `scanning`: Scheduled scan command now runs parallel library scans up to the configured concurrency limit; default scan interval changed to 12 hours

## Impact

- `app/Console/Commands/ScanLibraries.php` — use `Concurrency::run()` for parallel scanning
- `app/Settings.php` — add `scanConcurrency()` method
- `app/Http/Controllers/Settings/ScanSettingsController.php` — new controller
- `app/Http/Requests/UpdateScanSettingsRequest.php` — new form request
- `routes/settings.php` — add scan settings routes
- `resources/js/pages/settings/scan.tsx` — new settings page
- `app/Http/Requests/StoreLibraryRequest.php` — change default `scan_interval`
- `resources/js/pages/libraries/create.tsx` — change default to 43200
