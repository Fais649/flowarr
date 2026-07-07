# Flowarr — Architecture & Project Plan

## What It Is

Media library file manipulation automation webapp. Watches filesystem directories for media files, runs configured jobs (transcoding, subtitle extraction). Designed for private media servers running *arr stacks with Jellyfin/Plex.

**Stack:** Laravel 13, Inertia v3 + React 19, Tailwind v4, PostgreSQL 18, RabbitMQ, Redis, shadcn/ui.

---

## Directory Layout

```
app/
  Actions/Fortify/         — Fortify auth actions (CreateNewUser, ResetUserPassword)
  Concerns/                — Shared traits (PasswordValidationRules, ProfileValidationRules)
  Console/Commands/        — Scheduled commands (empty, to be built)
  Enums/                   — (intended directory, not yet created)
  Events/                  — Event classes (empty, to be built)
  Http/
    Controllers/Settings/  — Settings controllers (ProfileController, SecurityController)
    Middleware/             — HTTP middleware (HandleAppearance, HandleInertiaRequests)
    Requests/Settings/      — Form Request validation classes
  Jobs/                    — Queue jobs (TranscodeMediaJob, ProcessSubtitleJob, ConvertSubtitleJob)
  Models/                  — Eloquent models: User, Library, LibraryJob, Execution
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
  web.php                  — Inertia page routes (home, dashboard)
  settings.php             — Profile/security/appearance settings routes
  console.php              — Console command registration (inspire only)
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
    │ TranscodeMediaJob / ProcessSubtitleJob / ConvertSubtitleJob
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
| `ProcessSubtitleJob` | `extract_subs` | Extract text-based subtitle streams to .srt sidecars, strip from container |
| `ConvertSubtitleJob` | `convert_sub` | Placeholder/stub |

### Current State

Jobs accept `string $filePath` directly (no `executionId` / DB load). They run the ffmpeg/mkvmerge pipeline and log results. Status updates to the `Execution` model are not yet implemented.

---

## Queue Architecture

### Connection

RabbitMQ via `vladimir-yuldashev/laravel-queue-rabbitmq`. Configured as `QUEUE_CONNECTION=rabbitmq` in `.env`.

Default queue: `RABBITMQ_QUEUE=default` (not yet mapped to per-job queues in config).

### Queue Names (planned)

| Queue | Purpose |
|---|---|
| `transcode` | GPU-intensive ffmpeg jobs |
| `subtitle` | Lightweight subtitle extraction |
| `default` | Everything else |

Queue routing is not yet configured. Jobs use the default queue.

### Retry + Failure

Not yet configured. Jobs throw `RuntimeException` on failure. No retry policy, middleware, or dead-letter handling is set up.

---

## Existing Jobs

### TranscodeMediaJob (`app/Jobs/TranscodeMediaJob.php`)

- Accepts `string $filePath`
- Runs ffmpeg with `hevc_nvenc` encoder, HDR→SDR tonemap pipeline (`zscale`), audio copy
- Output: `{filepath}HEVC{ext}` (e.g., `video.mkvHEVC.mkv`)
- Timeout: 3600s
- Logs ffmpeg output + success/failure

### ProcessSubtitleJob (`app/Jobs/ProcessSubtitleJob.php`)

- Accepts `string $filePath`
- Probes with ffprobe for subtitle streams
- Extracts text-based codecs (srt, ass, ssa, webvtt, subrip) to `.srt` sidecar files with language-coded filenames
- Strips all internal subtitles from container via `mkvmerge -S`
- Uses `config('languages.php')` for ISO 639-2 → 639-1 mapping

### ConvertSubtitleJob (`app/Jobs/ConvertSubtitleJob.php`)

- Stub/placeholder, empty `handle()`

---

## Routes

### Web (`routes/web.php`)

| Method | Path | Page | Auth |
|---|---|---|---|
| GET | `/` | welcome | Public |
| GET | `/dashboard` | dashboard | Auth + Verified |

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

### Console (`routes/console.php`)

Single `inspire` command. No scheduled tasks.

---

## Frontend

### Pages

Auth: login, register, forgot-password, reset-password. Settings: profile, security (passkeys + password), appearance (theme toggle). Dashboard (empty shell). Welcome (landing).

Inertia shared data: `name` (app name), `auth.user`, `sidebarOpen` (persisted toggle).

### Components

shadcn/ui primitives: button, card, dialog, sidebar, sheet, dropdown-menu, badge, avatar, tooltip, skeleton, etc.

App layout: app-shell, app-sidebar (nav), app-header, app-content, breadcrumbs, heading.

Auth: passkey-register, passkey-verify, passkey-item, manage-passkeys, password-input.

---

## Implementation Plan (Upcoming)

### Phase 1: Job ↔ Execution Integration
- Jobs load `Execution` via `executionId` instead of raw `filePath`
- Jobs update Execution status/progress/message during run
- Create dedicated queue routing (transcode, subtitle queues)
- Add `Worker` model + registration

### Phase 2: Scanner Service
- Build `ScannerService` to walk libraries, create executions for new/changed files
- Scheduled `ScanLibraries` command
- File dedup via checksum or mtime+size

### Phase 3: API + CRUD
- API controllers for libraries, library jobs, executions
- Inertia management pages for each entity
- Form request validation

### Phase 4: Worker Management
- Worker heartbeat + capability detection
- Queue middleware (pause-on-stream, schedule windows)
- Worker status dashboard

### Phase 5: Integrations
- Media server session monitoring (Jellyfin/Plex/Emby)
- Post-process notifications
- Stream-aware pause/resume
