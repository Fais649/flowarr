## 1. Settings Infrastructure

- [x] 1.1 Create `database/migrations/2026_07_26_110444_create_settings_table.php` with `key` (unique string) and `value` (text) columns
- [x] 1.2 Create `App\Models\Setting` with key-value accessors and a `get(string $key, mixed $default)` helper method
- [x] 1.3 Create `App\Settings` helper that provides typed access to common settings (`concurrency('transcode')` and `isPaused()`)

## 2. Shared Job Logic

- [x] 2.1 Create `App\Jobs\Concerns\HasPauseAndConcurrency` trait with `shouldPause()`, `concurrencyLimit()`, `activeCount()`, and `isAtCapacity()` methods
- [x] 2.2 The concurrency check queries `Execution::where('status', ExecutionStatus::PROCESSING)->whereHas('libraryJob', fn($q) => $q->where('job_id', $jobType->value))->count()` and compares to the setting value
- [x] 2.3 Add `releaseIfAtCapacity(LibraryJobId $jobType): void` method that checks the limit and calls `$this->release(30)` if at capacity (guarded by `isset($this->job)`)

## 3. Wire Into Jobs

- [x] 3.1 Apply the trait to `TranscodeMediaJob` — calls `releaseIfAtCapacity()` at the top of `handle()`
- [x] 3.2 Apply the trait to `ExtractSubtitlesJob` — calls `releaseIfAtCapacity()` at the top of `handle()`
- [x] 3.3 Apply the trait to `ConvertSubtitleJob` — calls `releaseIfAtCapacity()` at the top of `handle()`
- [x] 3.4 All three jobs now share the same `shouldPause()` logic from the trait (reads `Settings::isPaused()` instead of Cache)

## 4. Backend — Settings Controller

- [x] 4.1 Create `App\Http\Controllers\Settings\WorkerSettingsController` with `edit()` and `update()` methods
- [x] 4.2 Create `App\Http\Requests\UpdateWorkerSettingsRequest` with validation (each limit must be integer >= 1, paused boolean)
- [x] 4.3 Add route `GET /settings/workers` (workers.edit) and `POST /settings/workers` (workers.update) in `routes/settings.php`
- [x] 4.4 Seed default concurrency values in `WorkerSettingsSeeder` (transcode: 1, extract_subs: 4, convert_sub: 4, paused: false)

## 5. Frontend — Worker Settings Page

- [x] 5.1 Create `resources/js/pages/settings/workers.tsx` with an Inertia page listing each job type with its current concurrency limit
- [x] 5.2 Each job type displays a label, description, and number input (min=1)
- [x] 5.3 Add "Workers" navigation link to settings sidebar in `resources/js/layouts/settings/layout.tsx`
- [x] 5.4 Wire form submission to `POST /settings/workers` with the updated values (uses `useForm` + `updateWorkerSettings.url()`)

## 6. Verification

- [x] 6.1 Run `vendor/bin/sail artisan test --compact` — 75 passed, 3 skipped, 0 failed
- [x] 6.2 Run `npm run types:check` — no TypeScript errors
- [x] 6.3 Run `vendor/bin/sail bin pint` — clean
