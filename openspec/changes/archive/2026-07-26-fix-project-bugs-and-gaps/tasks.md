## 1. Foundation

- [x] 1.1 Create `app/Jobs/Contracts/DispatchableJob.php` interface with `handle(): void`
- [x] 1.2 Add ffmpeg and mkvmerge config defaults to `config/services.php`
- [x] 1.3 Create new migration to rename `createdAt` → `created_at` on `workers` table

## 2. Fix ConvertSubtitleJob Data Loss Bug

- [x] 2.1 Move `unlink($this->filePath)` from `finally` block to success path after successful ffmpeg conversion
- [x] 2.2 Update `ConvertSubtitleJobTest` to verify source file is preserved on failure

## 3. Job Interface Implementation

- [x] 3.1 Implement `DispatchableJob` on `TranscodeMediaJob`
- [x] 3.2 Implement `DispatchableJob` on `ExtractSubtitlesJob`
- [x] 3.3 Implement `DispatchableJob` on `ConvertSubtitleJob`

## 4. Worker Model

- [x] 4.1 Create `App\Models\Worker` with fillable fields and proper casts
- [x] 4.2 Create `WorkerFactory` with valid default state
- [x] 4.3 Update `DatabaseSeeder` if needed

## 5. Factory Fixes

- [x] 5.1 Fix `LibraryFactory` — use `fake()->randomElement(LibraryStatus::cases())->value` for status, `fake()->numberBetween(60, 86400)` for scan_interval, nullable last_scan
- [x] 5.2 Fix `ExecutionFactory` — use `fake()->randomElement(ExecutionStatus::cases())->value` for status, realistic worker_id and file_path

## 6. Cleanup

- [x] 6.1 Move `tests/Feature/tests/Feature/TranscodeMediaJobTest.php` to `tests/Feature/TranscodeMediaJobTest.php`
- [x] 6.2 Delete now-empty nested `tests/Feature/tests/` directory
- [x] 6.3 Replace Symfony `now()` import with Laravel's `now()` helper in `ScanLibraryCommand.php`
