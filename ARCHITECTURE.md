# Flowarr — Architecture & Project Plan

## What It Is

Media library file manipulation automation webapp. Watches filesystem directories for media files, matches them against user-defined workflow rules, runs operations (transcoding, subtitle extraction). Designed for private media servers running *arr stacks with Jellyfin/Plex.

**Stack:** Laravel 13, Inertia v3 + React 19, Tailwind v4, PostgreSQL 18, RabbitMQ, Redis, shadcn/ui.

---

## Directory Layout

```
app/
  Console/Commands/        — Scheduled commands (scan, monitor streams, worker heartbeat)
  Enums/                   — PHP 8 backed enums
  Http/
    Controllers/Api/       — API controllers (CRUD for each entity)
    Requests/              — Form Request validation classes
    Resources/             — Eloquent API Resources
  Jobs/                    — Queue jobs (TranscodeMediaJob, ProcessSubtitleJob)
  Jobs/Middleware/          — Queue middleware (stream check, schedule window, capability gate)
  Models/                  — Eloquent models (7 entities + User)
  Providers/               — Service providers
  Services/                — Domain services (ScannerService, MediaServerService, EncoderService)
  Services/Integrations/   — Per-integration classes (JellyfinService, SonarrService, etc.)
database/
  migrations/              — Timestamped migrations (one concern per file)
  factories/               — Model factories for seeding/tests
  seeders/                 — Database seeders (Operation seeds, dev data)
  schema/                  — Reference SQL schema (kept in sync with migrations)
resources/js/
  pages/                   — Inertia pages (grouped by domain)
    MediaSources/
    Workflows/
    Executions/
    Workers/
    Integrations/
    Dashboard/
  components/              — Reusable React components
    ui/                    — shadcn/ui primitives
    flows/                 — Domain-specific components (condition builder, status badge)
routes/
  web.php                  — Inertia page routes
  api.php                  — JSON API routes
  settings.php             — Profile/security settings
  console.php              — Scheduled command registration
```

---

## Entity-Relationship Diagram

```
User ── MediaSource ── MediaFile
 │                        │
 ├── Workflow ────── Execution
 │     │                  │
 │     │                  └── Worker
 │     │
 │     └── Integration (polymorphic purpose — server monitor + notification target)
 │
 └── (owns everything via user_id)
```

**Key:** `Workflow` references `Operation` (seeded system type). `Workflow` optionally scopes to one `MediaSource` (null = all sources). `Execution` links a `Workflow` to a `MediaFile` and optionally to a `Worker`.

---

## Core Flow

```
MediaSource (FS root)
    │
    ▼
ScanFilesystem command (scheduled, configurable interval, default 5min)
    │ uses chunked directory walk + stat() + optional xxh3 checksum
    ▼
MediaFile created/updated
    │ status = original,  upserted by path
    ▼
Match against active Workflows where conditions match MediaFile
    │ dedup check: skip if (workflow_id, media_file_id, status=done) exists
    ▼
Execution created (pending)
    │ parameters_snapshot = frozen copy of Workflow.parameters at dispatch time
    ▼
Queue middleware gates (run in order before job handler):
  1) Stream active? (cache: workers.paused) → release(60)
  2) Outside schedule window? → release(until next window)
  3) Worker capabilities match operation? → release (unacknowledged, next worker picks up)
    │
    ▼ All clear
Worker claims job → Execution status = running
    │ ffmpeg/mkvmerge runs, progress updated via heartbeat
    ▼
Execution done/failed
    │ MediaFile.status updated
    │ If done + Integration.notify_on_complete → notify integrations
```

---

## Entity Schema

All tables use `ulid` as primary key. Timestamps use `timestamps()` (created_at, updated_at). Soft deletes on most entities.

### MediaSource — FS root to scan

```php
Schema::create('media_sources', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->string('name');
    $table->text('path');
    $table->integer('scan_interval')->default(300); // seconds
    $table->boolean('active')->default(true);
    $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->softDeletes();
});
```

FS is the source of truth. No *arr dependency for file discovery.

### MediaFile — Tracked media file

```php
Schema::create('media_files', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->foreignUlid('media_source_id')->nullable()->constrained()->nullOnDelete();
    $table->text('path')->unique();
    $table->bigInteger('size_bytes');
    $table->string('checksum', 64)->nullable(); // xxh3
    $table->string('mime_type')->nullable();
    $table->string('container_format')->nullable(); // mkv, mp4
    $table->string('video_codec')->nullable();      // hevc, h264, av1
    $table->string('video_resolution')->nullable(); // 1080p, 4k, 2160p
    $table->decimal('duration_seconds', 10, 3)->nullable();
    $table->string('status')->default('original');  // FileStatus enum in app
    $table->jsonb('metadata')->nullable();          // bitrate, HDR type, audio/sub tracks
    $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['status', 'updated_at'])->whereNull('deleted_at');
});
```

Scan uses `mtime + size` comparison on subsequent runs. Checksum only recomputed when `mtime` or `size` changes (or forced via deep scan).

### Operation — System-defined work type

Seeded, not user-creatable. Two initial rows: transcode_media, extract_subtitles.

```php
Schema::create('operations', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->string('type');       // OperationType enum in app
    $table->string('name');
    $table->string('class_ref');  // App\Jobs\TranscodeMediaJob::class
    $table->string('category');   // video, audio, subtitle
    $table->jsonb('config_schema')->nullable(); // UI form field definitions
    $table->boolean('active')->default(true);
    $table->timestamps();
});
```

### Workflow — User-defined automation

One Workflow = one Operation + conditions + trigger config + schedule.

```php
Schema::create('workflows', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->string('name');
    $table->foreignUlid('media_source_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignUlid('operation_id')->constrained()->cascadeOnDelete();
    $table->jsonb('conditions');     // match rules, AND logic
    $table->jsonb('parameters');     // operation params (encoder, preset, etc.)
    $table->string('trigger_type');  // TriggerType enum
    $table->jsonb('trigger_config')->nullable(); // cron expression or event names
    $table->jsonb('schedule_config')->nullable(); // timezone + time windows
    $table->smallInteger('parallelism')->default(1);
    $table->boolean('active')->default(true);
    $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->softDeletes();
});
```

Conditions example:
```json
[
    {"field": "video_codec", "operator": "not_in", "value": ["hevc", "av1"]},
    {"field": "video_resolution", "operator": "in", "value": ["4k", "2160p"]}
]
```

All conditions must match (AND). No OR support initially.

### Execution — Concrete run

One Execution = one Operation on one MediaFile, dispatched to one Worker.

```php
Schema::create('executions', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->foreignUlid('workflow_id')->constrained()->cascadeOnDelete();
    $table->foreignUlid('media_file_id')->constrained()->cascadeOnDelete();
    $table->foreignUlid('worker_id')->nullable()->constrained()->nullOnDelete();
    $table->string('status');        // ExecutionStatus enum
    $table->smallInteger('progress')->nullable(); // 0-100
    $table->text('message')->nullable();
    $table->jsonb('parameters_snapshot'); // frozen at dispatch
    $table->integer('duration_ms')->nullable();
    $table->bigInteger('size_input_bytes')->nullable();
    $table->bigInteger('size_output_bytes')->nullable();
    $table->timestamp('started_at')->nullable();
    $table->timestamp('finished_at')->nullable();
    $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
    $table->timestamps();

    // Pending/queued/running executions: fast lookup for queue worker
    $table->index(['status', 'created_at'])->whereNull('deleted_at')
          ->whereIn('status', ['pending', 'queued', 'running']);
    // Dedup: prevent re-creating execution for same workflow+file
    $table->index(['workflow_id', 'media_file_id', 'status']);
});
```

### Worker — Registered node

Heartbeat-based. Workers register themselves on startup and ping every 30s.

```php
Schema::create('workers', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->string('name');
    $table->string('ip_address')->nullable(); // inet in Postgres
    $table->jsonb('capabilities');  // ["gpu-nvenc", "gpu-vaapi", "cpu", "subtitle"]
    $table->timestamp('last_heartbeat_at')->nullable();
    $table->string('status');       // WorkerStatus enum
    $table->smallInteger('active_jobs')->default(0);
    $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->softDeletes();
});
```

Capabilities auto-detected at startup by probing ffmpeg encoder support.

### Integration — External service connection

Abstract table. Per-type logic lives in service classes, not schema.

```php
Schema::create('integrations', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->string('type');    // IntegrationType enum
    $table->string('name');
    $table->jsonb('config');   // Encrypted Cast: url, api_key, credentials
    $table->boolean('enabled')->default(true);
    $table->boolean('notify_on_complete')->default(false);
    $table->timestamp('last_used_at')->nullable();
    $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->softDeletes();
});
```

Config shapes per type (stored encrypted):
```jsonc
// jellyfin/plex/emby
{"url": "http://192.168.1.50:8096", "api_key": "abc123", "verify_ssl": false}
// sonarr/radarr/lidarr/readarr
{"url": "http://192.168.1.50:8989", "api_key": "xyz789"}
```

---

## Enums

```php
enum FileStatus: string         { case Original = 'original'; case Processing = 'processing'; case Processed = 'processed'; case Failed = 'failed'; }
enum OperationType: string      { case TranscodeMedia = 'transcode_media'; case ExtractSubtitles = 'extract_subtitles'; case RemuxContainer = 'remux_container'; }
enum TriggerType: string        { case Cron = 'cron'; case FileWatch = 'file_watch'; case Manual = 'manual'; }
enum ExecutionStatus: string    { case Pending = 'pending'; case Queued = 'queued'; case Running = 'running'; case Pausing = 'pausing'; case Paused = 'paused'; case Stopping = 'stopping'; case Stopped = 'stopped'; case Done = 'done'; case Failed = 'failed'; }
enum WorkerStatus: string       { case Idle = 'idle'; case Busy = 'busy'; case Paused = 'paused'; case Offline = 'offline'; }
enum IntegrationType: string    { case Jellyfin = 'jellyfin'; case Plex = 'plex'; case Emby = 'emby'; case Sonarr = 'sonarr'; case Radarr = 'radarr'; case Lidarr = 'lidarr'; case Readarr = 'readarr'; case QBittorrent = 'qbittorrent'; case SABnzbd = 'sabnzbd'; case Custom = 'custom'; }
```

Enum files in `app/Enums/`. String-backed for database compatibility.

---

## Existing Code To Refactor

### Jobs (app/Jobs/)

Two jobs exist and must be refactored in Phase 3:

| Current | Refactor Target |
|---|---|
| Accepts `string $filePath` in constructor | Accepts `string $executionId`, loads Execution from DB |
| Hardcoded ffmpeg/mkvmerge pipeline | Reads encoder + parameters from Execution.parameters_snapshot |
| No status reporting | Updates Execution.progress, status, duration, size delta |
| Logs to file only | Logs to Execution.message + Laravel log |

### Config (config/languages.php)

ISO 639-2 → ISO 639-1 mapping. Used by subtitle job for filename generation. Keep as-is.

### Routes (routes/)

Existing routes: auth (Fortify), dashboard, profile/security settings. Keep. New routes go in `routes/api.php` for CRUD endpoints + `routes/web.php` for Inertia pages.

---

## Queue Architecture

### Connection

RabbitMQ via `vladimir-yuldashev/laravel-queue-rabbitmq`. Default connection set to `rabbitmq` in `.env`.

### Queue Names

| Queue | Purpose |
|---|---|
| `transcode` | GPU-intensive ffmpeg jobs |
| `subtitle` | Lightweight subtitle extraction |
| `notifications` | Integration API calls (post-process notify) |
| `default` | Everything else |

### Dedup Strategy

Two layers:

1. **SQL gate** before Execution creation: `SELECT id FROM executions WHERE workflow_id = ? AND media_file_id = ? AND status = 'done'` → skip.
2. **Queue dedup** via `ShouldBeUnique` on jobs when applicable (prevent duplicate same-file dispatches).

### Retry + Failure

- Transcode: 3 retries with exponential backoff `[60, 300, 900]` seconds
- Subtitle extraction: 1 retry (fast operation, re-run is cheap)
- Notifications: 5 retries with backoff (external API can be flaky)
- `retry_after` must exceed job `timeout`
- Failed jobs stored in `failed_jobs` table, exposed in dashboard for manual retry

### Job Middleware (run order)

Applied to transcode + subtitle queues:

```php
// 1. WorkerPausedGate — checks cache('workers.paused')
// 2. ScheduleWindowGate — checks Workflow.schedule_config
// 3. WorkerCapabilityGate — checks Worker.capabilities vs Operation requirements
```

Middleware releases job back to queue (unacknowledged) if gate blocks. RabbitMQ redelivers to next available worker.

---

## Pause-on-Stream System

```
MonitorStreams command (scheduled every 30s):
  → Integration::whereIn('type', [jellyfin, plex, emby])->where('enabled', true)->get()
  → For each: poll /Sessions endpoint
  → Count sessions where NowPlaying ≠ null
  → If count > 0 AND workers currently unpaused:
        cache()->put('workers.paused', count, 120)
        Log: "Paused workers — {count} active stream(s)"
  → If count = 0 AND workers currently paused:
        cache()->forget('workers.paused')
        Log: "Resumed workers — 0 active streams"

Queue middleware WorkerPausedGate:
  → if cache('workers.paused'): $this->release(60)
```

In-progress jobs finish normally. Only new job claims blocked.

---

## Caching Strategy

| Key | TTL | Purpose |
|---|---|---|
| `workers.paused` | 120s (refreshed every poll) | Pause flag, auto-expires if monitor stops |
| `scanner.{media_source_id}.mtime` | 300s | Last mtime snapshot per source |
| `worker.{worker_id}.heartbeat` | 60s | Worker liveness |
| `integration.{id}.sessions` | 30s | Jellyfin session cache (avoid hammering API) |

`Cache::flexible()` for session cache (stale-while-revalidate) — serves stale data if refresh fails, avoids blocking queue on unavailable media server.

---

## Filesystem Scanner

### Scan Algorithm

```
1. Load all active MediaSources
2. For each source:
   a. Walk directory recursively (use DirectoryIterator or recursive opendir)
   b. For each file:
      - stat() → size + mtime
      - Look up MediaFile by path
      - If not found: new file → compute checksum, INSERT
      - If found + mtime/size unchanged: skip
      - If found + mtime/size changed: recompute checksum, UPDATE
   c. Soft-delete MediaFiles under this source not found on disk
```

### Performance

- Pure stat() walk: ~5000 files/second (negligible time for any media library)
- xxh3 checksum: ~1GB/second (disk-limited)
- Subsequent scans: only checksum files where mtime or size changed (usually 0-5 files)
- Use `chunkById()` or `lazyById()` for batch upserts against DB

### Initial Scan

First scan of a MediaSource inserts all files. Subsequent scans are incremental. Toggle checksum via `MediaSource.scan_interval`: set to `-1` for checksum, `>= 0` for stat-only.

---

## Implementation Phases

### Phase 1: Foundation

Models and migrations build the data layer everything depends on.

```
Artisan commands:
  make:enum (6 enums)
  make:migration (7 tables)
  make:model (7 models, with --factory)
  migrate

Verify:
  psql \dt — all tables exist
  artisan tinker — MediaSource::factory()->create() works
  pint --format agent — code style clean
```

### Phase 2: FS Scanner

```
Files:
  app/Services/Filesystem/ScannerService.php
  app/Console/Commands/ScanMediaSources.php

Schedule: ScanMediaSources every 5min in console.php

Tests:
  - Scan empty dir → zero MediaFiles created
  - Scan dir with files → MediaFiles created with correct metadata
  - Re-scan with unchanged files → no new MediaFiles
  - Re-scan with modified file → MediaFile updated
```

### Phase 3: Execution Engine

```
Files:
  database/seeders/OperationSeeder.php
  app/Services/ExecutionDispatcher.php (match workflows → create executions → dispatch)
  app/Jobs/Middleware/WorkerPausedGate.php
  app/Jobs/Middleware/ScheduleWindowGate.php
  app/Jobs/Middleware/WorkerCapabilityGate.php
  app/Services/EncoderDetector.php (detect available encoders)

Modify:
  TranscodeMediaJob → accept executionId, read from DB
  ProcessSubtitleJob → accept executionId, read from DB

Seed: operations table with transcode_media, extract_subtitles
Schedule: dispatch command every 1min (check workflow matches on new/changed files)

Tests:
  - Match workflow creates execution
  - Execution status transitions correctly
  - Existing jobs updated via executionId
```

### Phase 4: Workflow + MediaSource CRUD

```
Files:
  app/Http/Controllers/Api/MediaSourceController.php
  app/Http/Controllers/Api/WorkflowController.php
  app/Http/Requests/StoreMediaSourceRequest.php
  app/Http/Requests/UpdateWorkflowRequest.php
  resources/js/pages/MediaSources/
  resources/js/pages/Workflows/

Routes: apiResource for both entities

Tests:
  - CRUD operations via API
  - Form Request validation
  - Inertia page rendering
```

### Phase 5: Stream Monitoring + Worker Control

```
Files:
  app/Services/Integrations/MediaServerService.php (abstract + Jellyfin implementation)
  app/Console/Commands/MonitorStreams.php
  app/Console/Commands/WorkerHeartbeat.php
  app/Jobs/Middleware/WorkerPausedGate.php (finalize)
  app/Jobs/Middleware/ScheduleWindowGate.php (finalize)

Schedule: MonitorStreams every 30s, WorkerHeartbeat every 60s

Tests:
  - Monitor parses Jellyfin sessions correctly
  - Cache flag set/cleared based on active count
  - Queue middleware blocks/releases based on cache
```

### Phase 6: Dashboard + Refinement

```
Files:
  app/Http/Controllers/Api/DashboardController.php
  resources/js/pages/Dashboard/
  resources/js/pages/Executions/
  app/Http/Controllers/Api/ExecutionController.php
  app/Http/Controllers/Api/IntegrationController.php

Routes: dashboard stats endpoint, execution history, integration CRUD

Tests:
  - Dashboard returns correct aggregate counts
  - Execution filters by status
  - Integration CRUD
```

---

## Architectural Decisions

| Decision | Rationale |
|---|---|
| FS as source of truth for files | Always accurate. Works with any setup. No *arr dependency for core function. |
| Ulid over UUID for PKs | Distributed-safe (queue workers create records concurrently). ULID is sortable by time (better index locality). |
| No separate Rule table | Conditions live on Workflow as JSON. One Workflow = one Op. Keeps schema flat. |
| Queue middleware for all gates | Workers always running. Gates release jobs back to queue when blocked. No supervisor restart needed. |
| Integration as single abstract table | Extensible via `type` column + per-type service classes. No schema change for new integrations. |
| xxh3 for checksums | Fastest non-cryptographic hash. ~1GB/sec throughput. Enough for dedup (not security). |
| jsonb for conditions/parameters/config | Schema flexibility without migrations. Postgres jsonb is indexable and queryable if needed later. |
| `encrypted` cast on Integration.config | Sensitive data (API keys, passwords). Laravel's encryption at rest. |
| `constrained()` in migrations | Laravel convention. Automatically infers FK table name, adds index. |
| Soft deletes on most entities | Recovery from accidental deletion. Preserves referential integrity for historical executions. |
| Per-Workflow parallelism cap | Prevents user from accidentally saturating GPU with too many concurrent transcodes. |
| `chunkById()` for large DB operations | Memory-safe batch processing. Prevents RAM exhaustion on large libraries. |

---

## Key Design Principles

1. **FS is SSOT.** MediaSource + scan command is the only required path for file discovery. Everything else is optional.
2. **Integrations are optional extras.** All integration features live behind abstract service contracts. App works with zero integrations configured.
3. **Distributed by default.** RabbitMQ handles work distribution. Workers just need shared NAS mount and queue access. No central orchestrator.
4. **Pause is non-destructive.** In-progress jobs finish naturally. Only new job claims are blocked. Resume is automatic.
5. **Single-user by design.** `user_id` FK exists for data scoping. Not multi-tenant. Not designed for shared hosting.
6. **Idempotent scanners.** Scanning the same filesystem twice produces the same result. Re-running a scan after failure is safe.
7. **Observable by default.** Every Execution tracks status, progress, duration, and size delta. Dashboard surfaces queue depth and worker health.
