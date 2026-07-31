# Flowarr — Architecture & Project Plan

## What It Is

Media library file manipulation automation webapp. Watches filesystem directories for media files, runs configured jobs (transcoding, subtitle extraction). Designed for private media servers running *arr stacks with Jellyfin/Plex.

**Stack:** Laravel 13, Inertia v3 + React 19, Tailwind v4, PostgreSQL 18, Redis, shadcn/ui. Queues run on Laravel's database driver (`QUEUE_CONNECTION=database`); Redis is the production cache store.

---

## Directory Layout

```
app/
  Actions/Fortify/         — Fortify auth actions (CreateNewUser, ResetUserPassword)
  Concerns/                — Shared traits (PasswordValidationRules, ProfileValidationRules)
  Console/Commands/        — Console commands (ScanLibraries, OrchestrateQueueWorkers, CleanupStaleExecutions, AdminRecoverCommand)
  Enums/                   — (intended directory, not yet created)
  Events/                  — Event classes (empty, to be built)
  Http/
    Controllers/Settings/  — Settings controllers (ProfileController, SecurityController)
    Middleware/             — HTTP middleware (HandleAppearance, HandleInertiaRequests)
    Requests/Settings/      — Form Request validation classes
  Jobs/                    — Queue jobs (TranscodeMediaJob, ExtractSubtitlesJob, ConvertSubtitleJob, ScanLibraryJob, OrchestrateWorkersJob)
  Models/                  — Eloquent models: User, Library, LibraryJob, Execution, Worker, JobWorkerLimit, Setting
  Providers/               — Service providers (AppServiceProvider, FortifyServiceProvider)
  ExecutionStatus.php      — Backed enum (queued, processing, completed, stopped, paused, failed)
  LibraryJobId.php         — Backed enum (transcode_media, extract_subs, convert_sub) + job class map
  LibraryStatus.php        — Backed enum (pending, pending_scan, scanning, paused, stopped)
database/
  migrations/              — Timestamped migrations
  factories/               — Model factories
  seeders/                 — DatabaseSeeder (creates test user)
resources/js/
  pages/                   — Inertia pages
    auth/                  — Login, register, forgot-password, reset-password
    settings/              — Profile, security, appearance
    libraries/             — Libraries index, create, show
    executions/            — Executions index, show
    workers/               — Workers index, show
    config/                — Scan settings
    dashboard.tsx          — Dashboard
    welcome.tsx            — Landing page
  components/              — Reusable React components
    ui/                    — shadcn/ui primitives (button, card, dialog, sidebar, etc.)
    app-shell.tsx          — App layout shell
    app-sidebar.tsx        — Navigation sidebar
    nav-*.tsx              — Navigation components
    passkey-*.tsx          — WebAuthn/passkey components
    breadcrumbs.tsx        — Breadcrumb navigation
    heading.tsx            — Page heading
routes/
  web.php                  — Web routes (home, libraries, executions, workers, jellyfin webhook)
  settings.php             — Profile/security/appearance settings routes
  config.php               — Config/scan settings routes
  api.php                  — Playback route (not registered; see Routes section)
  console.php              — Console command registration + scheduled scan:libraries
```

---

## Entity-Relationship Diagram

```
User ── Library ── LibraryJob ── Execution
```

Each `Library` has one or more `LibraryJob` records (one per job type to run). Each `LibraryJob` produces `Execution` records as jobs are dispatched.

---

## Entity Schema

All tables use auto-increment integer primary keys.

### User — Application user

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->string('two_factor_secret')->nullable();
    $table->text('two_factor_recovery_codes')->nullable();
    $table->timestamp('two_factor_confirmed_at')->nullable();
    $table->timestamps();
});
```

WebAuthn passkeys stored in a separate `passkeys` table (Fortify default schema).

### Library — FS root with associated job configuration

```php
Schema::create('libraries', function (Blueprint $table) {
    $table->id();
    $table->string('base_path');
    $table->string('status');              // LibraryStatus enum
    $table->integer('scan_interval');
    $table->timestamp('last_scan')->nullable();
    $table->timestamps();
});
```

### LibraryJob — Job type enabled for a library

```php
Schema::create('library_jobs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('library_id');       // FK to libraries
    $table->string('job_id');              // LibraryJobId enum
    $table->timestamps();
});
```

`job_id` maps to a `LibraryJobId` backed enum which also provides the job class to dispatch via `getJobClass()`.

### Execution — Concrete run of a job on a file

```php
Schema::create('executions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('library_job_id');   // FK to library_jobs
    $table->string('worker_id');
    $table->string('file_path');
    $table->string('status');              // ExecutionStatus enum
    $table->timestamp('started_at')->nullable();
    $table->timestamp('finished_at')->nullable();
    $table->timestamps();
});
```

---

## Enums

```php
enum LibraryStatus: string    { case PENDING = 'pending'; case PENDING_SCAN = 'pending_scan'; case SCANNING = 'scanning'; case PAUSED = 'paused'; case STOPPED = 'stopped'; }
enum LibraryJobId: string     { case TRANSCODE_MEDIA = 'transcode_media'; case EXTRACT_SUBTITLES = 'extract_subs'; case CONVERT_SUBTITLE = 'convert_sub'; }
enum ExecutionStatus: string  { case QUEUED = 'queued'; case PROCESSING = 'processing'; case COMPLETED = 'completed'; case STOPPED = 'stopped'; case PAUSED = 'paused'; case FAILED = 'failed'; }
```

`LibraryJobId` includes `getJobClass(): string` which maps each case to its job class.

---

## Core Flow

```
Library (FS root)
    │
    ▼
Scan command (scheduled)
    │ walks directory, finds media files
    ▼
For each LibraryJob (transcode, subtitle, etc.):
    │ creates Execution for each file
    ▼
Execution created (queued)
    │
    ▼
Queue worker picks up job
    │ TranscodeMediaJob / ExtractSubtitlesJob / ConvertSubtitleJob
    ▼
Job runs ffmpeg/mkvmerge pipeline
    │ updates Execution status to processing → completed/failed
    ▼
Execution done/failed
```

---

## Job Routing

Jobs are dispatched based on `LibraryJobId` mapping. The enum `getJobClass()` returns the fully qualified class name:

| Job | LibraryJobId | Purpose |
|---|---|---|
| `TranscodeMediaJob` | `transcode_media` | GPU-accelerated HDR→SDR tonemap + HEVC encode via ffmpeg |
| `ExtractSubtitlesJob` | `extract_subs` | Extract text-based subtitle streams to .srt sidecars, strip from container |
| `ConvertSubtitleJob` | `convert_sub` | Convert subtitle files to .srt |

### Current State

Jobs accept `string $filePath` plus a `bool $replaceOriginal` flag and an optional `int $executionId`. Via the `TracksExecution` trait (`app/Jobs/Concerns/TracksExecution.php`) they update the `Execution` status (processing → completed/failed) when an `executionId` is set; without one the status calls are no-ops. Jobs also pause while `media_processing_paused` is set or `active_streams` > 0 in cache. `ScannerService` creates the `Execution` record, passes its id via `setExecutionId()`, and dispatches the job.

---

## Queue Architecture

### Connection

Laravel's database queue driver: `QUEUE_CONNECTION=database` in `.env.example` and `docker-compose.prod.yml`. `config/queue.php` defaults to the `database` connection (`jobs` table, `retry_after` 90s) and defines per-job-type queues under `queue.queues` — `transcode`, `subtitle`, `convert-subs`, `default` — each overridable via `QUEUE_TRANSCODE`, `QUEUE_SUBTITLE`, `QUEUE_CONVERT_SUBS`, `QUEUE_DEFAULT`. Jobs select their queue in the constructor via `onQueue(...)`; `LibraryJobId::getQueue()` maps `transcode_media` → `transcode` and `extract_subs`/`convert_sub` → `subtitle`.

### Queue Names

| Queue | Purpose |
|---|---|
| `orchestration` | Queue-worker pool orchestration (`OrchestrateWorkersJob`) |
| `transcode` | GPU-intensive ffmpeg jobs (`TranscodeMediaJob`) |
| `subtitle` | Subtitle extraction/conversion (`ExtractSubtitlesJob`, `ConvertSubtitleJob`) |
| `convert-subs` | Configured queue with a supervisord program; no job dispatches to it yet |
| `default` | Everything else (`ScanLibraryJob`) |

Workers are supervisord programs (`docker/prod/supervisord.conf`): `orchestrator` consumes the `orchestration` queue with `--tries=1`; `Transcoder` (`transcode`), `ExtractSubs` (`subtitle`), and `ConvertSubs` (`convert-subs`) each run `--tries=3 --sleep=3 --max-time=3600` with `numprocs=10` and `autostart=false`. An `orchestrate-startup` program runs `php artisan queue:orchestrate` at container start.

### Orchestration

`queue:orchestrate` (`app/Console/Commands/OrchestrateQueueWorkers.php`) dispatches an `OrchestrateWorkersJob` onto the `orchestration` queue (delayed 10s). The job reads `Worker` rows (`job_type`, `enabled`, `concurrency`), maps each to a program name (`Transcoder`, `ExtractSubs`, `ConvertSubs`), and scales processes 0–9 via `SupervisorService` (`supervisorctl start/stop <program>:<program>_NN`): starts processes up to `concurrency`, stops the rest. `WorkerObserver` re-dispatches the job on worker create/update/delete, so the pool resizes on configuration changes.

### Retry + Failure

Workers run with `--tries=3` (`Transcoder`, `ExtractSubs`, `ConvertSubs`) and `--tries=1` (`orchestrator`). On failure, jobs mark the `Execution` failed via `TracksExecution` and throw (`RuntimeException` in `TranscodeMediaJob`, `Exception` in `ConvertSubtitleJob`); `ExtractSubtitlesJob` catches failures internally, marks the Execution failed, and logs without rethrowing. Failed jobs are recorded in the `failed_jobs` table (`QUEUE_FAILED_DRIVER=database-uuids`).

---

## Existing Jobs

### TranscodeMediaJob (`app/Jobs/TranscodeMediaJob.php`)

- Accepts `string $filePath`, `bool $replaceOriginal`, optional `int $executionId`; routed to the `transcode` queue
- Runs ffmpeg with `hevc_nvenc` encoder (or `libx265` when `services.ffmpeg.use_nvenc` is false), HDR→SDR tonemap pipeline (`zscale`), audio copy
- Output: `_HEVC` inserted before the extension (e.g., `video.mkv` → `video_HEVC.mkv`); with `replaceOriginal` the original file is replaced
- No in-job timeout (`setTimeout(null)`; supervisord workers cap runtime via `--max-time=3600`)
- Updates `Execution` status (processing/completed/failed) via the `TracksExecution` trait; pauses while `media_processing_paused` is set or `active_streams` > 0
- Logs ffmpeg output + success/failure

### ExtractSubtitlesJob (`app/Jobs/ExtractSubtitlesJob.php`)

- Formerly `ProcessSubtitleJob`; accepts `string $filePath`, `bool $replaceOriginal`, optional `int $executionId`; routed to the `subtitle` queue
- Probes with ffprobe for subtitle streams
- Extracts text-based codecs (subrip, srt, ass, ssa, webvtt) to `.srt` sidecar files with language-coded filenames
- Strips all internal subtitles from container via `mkvmerge -S` when `replaceOriginal` is true
- Uses `config('languages.php')` for ISO 639-2 → 639-1 mapping
- Updates `Execution` status via the `TracksExecution` trait; pauses while streams are active

### ConvertSubtitleJob (`app/Jobs/ConvertSubtitleJob.php`)

- Converts subtitle files (any extension except `.srt`) to `.srt` via ffmpeg (`-c:s srt`); deletes the original when `replaceOriginal` is set
- Accepts `string $filePath`, `bool $replaceOriginal`, optional `int $executionId`; routed to the `subtitle` queue
- Updates `Execution` status via the `TracksExecution` trait; pauses while streams are active

---

## Routes

### Web (`routes/web.php`)

| Method | Path | Page / Controller | Auth |
|---|---|---|---|
| GET | `/` | welcome / redirect to register or dashboard | Public |
| POST | `/webhooks/jellyfin` | JellyfinWebhookController | Public (optional `X-Flowarr-Token`) |
| GET | `/dashboard` | dashboard | Auth + Verified |
| GET | `/libraries/directories` | DirectoryController | Auth + Verified |
| GET | `/libraries` | LibrariesController::index | Auth + Verified |
| GET | `/libraries/create` | LibrariesController::create | Auth + Verified |
| POST | `/libraries` | LibrariesController::store | Auth + Verified |
| GET | `/libraries/{library}` | LibrariesController::show | Auth + Verified |
| GET | `/libraries/{library}/edit` | LibrariesController::edit | Auth + Verified |
| PATCH | `/libraries/{library}` | LibrariesController::update | Auth + Verified |
| DELETE | `/libraries/{library}` | LibrariesController::destroy | Auth + Verified |
| POST | `/libraries/{library}/scan` | LibrariesController::triggerScan | Auth + Verified |
| POST | `/libraries/{library}/toggle-job` | LibrariesController::toggleJob | Auth + Verified |
| POST | `/libraries/{library}/toggle-worker` | LibrariesController::toggleWorker | Auth + Verified |
| GET | `/executions` | ExecutionsController::index | Auth + Verified |
| GET | `/executions/{execution}` | ExecutionsController::show | Auth + Verified |
| POST | `/executions/batch/{start,pause,resume,stop,delete}` | ExecutionsController::batch* | Auth + Verified |
| POST | `/executions/{execution}/{retry,cancel,start,pause,resume,stop}` | ExecutionsController::* | Auth + Verified |
| DELETE | `/executions/{execution}` | ExecutionsController::destroy | Auth + Verified |
| GET | `/workers` | WorkersController::index | Auth + Verified |
| GET | `/workers/{worker}` | WorkersController::show | Auth + Verified |
| PATCH | `/workers/{worker}` | WorkersController::update | Auth + Verified |
| POST | `/workers/{start-all,pause-all,resume-all,stop-all}` | WorkersController::*All | Auth + Verified |
| POST | `/workers/{worker}/{start,pause,resume,stop}` | WorkersController::* | Auth + Verified |
| POST | `/debug/restore-test-data` | DebugController | Local only |

### Settings (`routes/settings.php`)

| Method | Path | Controller | Auth |
|---|---|---|---|
| GET | `/settings` | Redirect → /settings/profile | Auth |
| GET | `/settings/profile` | ProfileController::edit | Auth |
| PATCH | `/settings/profile` | ProfileController::update | Auth |
| DELETE | `/settings/profile` | ProfileController::destroy | Auth+Verified |
| GET | `/settings/security` | SecurityController::edit | Auth+Verified |
| PUT | `/settings/password` | SecurityController::update | Auth+Verified |
| GET | `/settings/appearance` | Inertia page | Auth+Verified |
| GET | `/.well-known/passkey-endpoints` | JSON response | Public |

### Config (`routes/config.php`)

| Method | Path | Controller | Auth |
|---|---|---|---|
| GET | `/config/scan` | ScanSettingsController::edit | Auth + Verified |
| POST | `/config/scan` | ScanSettingsController::update | Auth + Verified |

### API (`routes/api.php`)

| Method | Path | Controller | Auth |
|---|---|---|---|
| POST | `/playback` | PlaybackController | — |

Note: `routes/api.php` exists on disk but is not registered — `bootstrap/app.php` `withRouting()` only loads `web.php`, `console.php`, and the `/up` health route. The `/playback` route is therefore not active.

### Console (`routes/console.php`)

Defines the `inspire` command and schedules `scan:libraries` every minute (`Schedule::command('scan:libraries')->everyMinute()`).

---

## Frontend

### Pages

Auth: login, register, forgot-password, reset-password. Settings: profile, security (passkeys + password), appearance (theme toggle). Libraries: index, create, show. Executions: index, show. Workers: index, show. Config: scan settings. Dashboard. Welcome (landing).

Inertia shared data: `name` (app name), `auth.user`, `sidebarOpen` (persisted toggle).

### Components

shadcn/ui primitives: button, card, dialog, sidebar, sheet, dropdown-menu, badge, avatar, tooltip, skeleton, etc.

App layout: app-shell, app-sidebar (nav), app-header, app-content, breadcrumbs, heading.

Auth: passkey-register, passkey-verify, passkey-item, manage-passkeys, password-input.

---

## Implementation Plan (Upcoming)

### Phase 1: Job ↔ Execution Integration
- Jobs load `Execution` via `executionId` instead of raw `filePath` — (done)
- Jobs update Execution status/progress/message during run — (done, status updates only via `TracksExecution`; no progress/message)
- Create dedicated queue routing (transcode, subtitle queues) — (done)
- Add `Worker` model + registration — (done)

### Phase 2: Scanner Service
- Build `ScannerService` to walk libraries, create executions for new/changed files — (done)
- Scheduled `ScanLibraries` command — (done)
- File dedup via checksum or mtime+size — (done via existing-Execution lookup; not checksum/mtime)

### Phase 3: API + CRUD
- API controllers for libraries, library jobs, executions
- Inertia management pages for each entity — (done)
- Form request validation — (done)

### Phase 4: Worker Management
- Worker heartbeat + capability detection
- Queue middleware (pause-on-stream, schedule windows) — (done for pause-on-stream, via in-job `shouldPause()` checks + `Queue::pause()/resume()`; no queue middleware or schedule windows)
- Worker status dashboard — (done)

### Phase 5: Integrations
- Media server session monitoring (Jellyfin/Plex/Emby) — (done for Jellyfin, via webhook; no Plex/Emby)
- Post-process notifications
- Stream-aware pause/resume — (done)
