## 1. Backend — Scan Concurrency Setting

- [x] 1.1 Add `Settings::scanConcurrency(): int` method reading `scan.concurrency` with default `2`
- [x] 1.2 Create `ScanSettingsController` (edit + update) following `WorkerSettingsController` pattern
- [x] 1.3 Create `UpdateScanSettingsRequest` with validation (`required|integer|min:1`)
- [x] 1.4 Register scan settings routes in `routes/settings.php`

## 2. Backend — Parallel Scanning

- [x] 2.1 Update `ScanLibraries::handle()` to limit concurrent scans via `take($maxConcurrent)` — scheduler runs every 60s so libraries are naturally rate-limited per tick

## 3. Default Scan Interval

- [x] 3.1 Change default `scan_interval` from 3600 to 43200 in `resources/js/pages/libraries/create.tsx`

## 4. Frontend — Scan Settings UI

- [x] 4.1 Create `resources/js/pages/settings/scan.tsx` with concurrency input (following workers page pattern)
- [x] 4.2 Add navigation link to scan settings in sidebar (if scan link doesn't exist yet)

## 5. Tests

- [x] 5.1 Add test: `Settings::scanConcurrency()` returns default of 2 when not configured
- [x] 5.2 Add test: `ScanLibraries` respects concurrency limit
