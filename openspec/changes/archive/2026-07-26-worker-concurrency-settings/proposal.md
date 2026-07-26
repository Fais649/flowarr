## Why

The number of concurrent jobs running per job type is currently uncontrolled — whatever number of queue workers happen to be running, that's the concurrency. Users need to be able to set limits per job type from the UI so they can tune how many GPU-heavy transcode jobs run simultaneously vs. lightweight subtitle jobs.

## What Changes

- Add a `settings` database table for key-value application settings
- Add concurrency settings for each job type (transcode, extract subs, convert subs) in the settings table
- Add a Settings page in the UI for viewing and editing these values
- Add a `SettingsController` for serving the settings page and updating values
- Jobs check their concurrency limit before starting — if at capacity, they release back to the queue with a delay
- All jobs must share the same `shouldPause()` conditions + concurrency check pattern

## Capabilities

### New Capabilities

- `worker-concurrency`: Per-job-type concurrency limits configurable from the UI, enforced by jobs at runtime

### Modified Capabilities

- `transcoding`: TranscodeMediaJob checks concurrency limit before starting ffmpeg
- `subtitle-extraction`: ExtractSubtitlesJob checks concurrency limit before probing
- `subtitle-conversion`: ConvertSubtitleJob checks concurrency limit before converting

## Impact

- `database/migrations/` — new migration for `settings` table
- `app/Models/Setting.php` — new model for key-value settings
- `app/Http/Controllers/Settings/WorkerSettingsController.php` — new controller
- `app/Http/Requests/UpdateWorkerSettingsRequest.php` — validation
- `routes/settings.php` — add settings routes
- `resources/js/pages/settings/workers.tsx` — new settings page
- `resources/js/Layouts/AppLayout.tsx` or sidebar — add nav link to worker settings
- `app/Jobs/TranscodeMediaJob.php` — add concurrency check before processing
- `app/Jobs/ExtractSubtitlesJob.php` — add concurrency check before processing
- `app/Jobs/ConvertSubtitleJob.php` — add concurrency check before processing
