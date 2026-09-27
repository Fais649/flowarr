# Flowarr — Architecture

## What It Is

Self-hosted media library automation. Flowarr scans directories for media files, queues jobs that transcode video to HEVC and turn subtitles into SRT sidecars, and holds all processing while a media server (Jellyfin, Plex, Emby) is streaming, or outside a configured time window.

**Stack:** Laravel 13, Inertia v3 + React 19, Tailwind v4, shadcn/ui, PostgreSQL 18, Redis (cache). Queues use the database driver. The production image runs nginx, PHP-FPM, the scheduler and all queue workers under supervisord.

---

## Directory Layout

```
app/
  Console/Commands/        scan:libraries, scan:cleanup, executions:reap, queue:orchestrate, app:admin-recover
  Http/
    Controllers/           Web UI controllers + MediaServerWebhookController
    Controllers/Api/       REST API (/api/v1)
    Controllers/Config/    Scan + processing settings
    Middleware/            AuthenticateApiToken, RedirectIfNoUsers, Inertia/appearance
    Requests/              Form requests
    Resources/             API resources
  Jobs/                    ExecutionJob (TranscodeMedia, ExtractSubtitles, ConvertSubtitle), ScanLibrary,
                           OrchestrateWorkers, SendExecutionNotification
  Models/                  User, Library, LibraryJob, Execution, Worker, Setting
  Observers/               WorkerObserver (re-orchestrates the process pool on config changes)
  Services/                See "Services" below
  ExecutionStatus.php      queued | processing | paused | completed | stopped | failed
  LibraryJobId.php         transcode_media | extract_subs | convert_sub (+ job class, label, supervisor program)
  LibraryStatus.php        pending | pending_scan | scanning | paused | stopped
  MediaJobQueue.php        transcode-media | extract-subtitles | convert-subtitle
  OrchestrateJobQueue.php  orchestrate-workers | scan-libraries | notifications
  Settings.php             Typed accessors for the settings table
scripts/                   transcode_media.sh, extract_subtitles.sh, convert_subtitle.sh (the actual ffmpeg work)
docker/prod/               Production entrypoint, nginx, php.ini, supervisord.conf
```

---

## Entities

```
Library ──< library_worker >── Worker
   │                              │
   └──< LibraryJob ──< Execution >┘
```

| Model | Purpose |
|---|---|
| `Library` | A directory (`base_path`) with a `scan_interval`, scan `status` and `last_scan`. |
| `Worker` | One configuration per job type: `enabled`, `concurrency` (1–10 processes) and `replace_original`. Default workers are created by migration. |
| `library_worker` | Which workers (jobs) run for which library. |
| `LibraryJob` | Groups the executions of one job type within a library (created on demand by the scanner). |
| `Execution` | One run of a job on one file: `status`, `progress`, `message` (failure reason / hold reason), `output` (process log tail), `worker_id`, `file_size` + `file_mtime` fingerprint, `started_at`, `heartbeat_at`, `finished_at`. |
| `Setting` | Key/value settings: scan concurrency, manual pause, processing window, stream timeout, notification webhook, API token hash. |

---

## Core Flow

```
scan:libraries (every minute)          "Scan now" / new library / API
        │                                        │
        └──────────── ScanLibrary job ◄──────────┘   (atomically claims the library: status → scanning)
                            │
                     ScannerService
                            │  walks the library, skips hidden/temp files and junk dirs,
                            │  probes each file once, and for every enabled worker decides
                            │  whether the job is needed and not already handled
                            ▼
                Execution (queued) + TranscodeMedia / ExtractSubtitles / ConvertSubtitle job
                            │
                  queue worker (supervisord pool)
                            │
                ProcessExecutionService
                   1. wait while the ProcessingGate is closed
                   2. start scripts/<job>.sh
                   3. every second: apply pause/resume/stop from the DB and the gate
                      (SIGSTOP / SIGCONT / SIGTERM to the whole process tree)
                   4. every 5s: heartbeat, progress (parsed from ffmpeg time=), output tail
                   5. completed | failed (with reason) | stopped
                            │
                ExecutionNotifier → SendExecutionNotification (optional webhook)
```

### Deduplication

The scanner looks at the latest execution for each (job, file):

- active (queued/processing/paused) or stopped by the user → skip
- completed or failed and the file's size + mtime are unchanged → skip
- the file changed since (e.g. re-downloaded) → evaluate it again

Failed files are therefore not retried on every scan; use **Retry** in the UI/API.

### What each job does

| Job | Needed when | Script behaviour |
|---|---|---|
| Transcode | video codec is not HEVC | NVENC → VAAPI → libx265 (auto-detected or `TRANSCODE_HW_MODE`), HDR sources tonemapped to SDR, all audio/subtitle/attachment streams kept. Writes `<name>_hevc.mkv`, or replaces the original with `<name>.mkv` when `replace_original` is on. Falls back to libx265, then to dropping subtitles, then to no filter. |
| Extract subtitles | video has text subtitle streams (SRT/ASS/SSA/WebVTT/mov_text) | Writes `<target>.<lang>[.forced].srt` sidecars named after the future transcode output. With `replace_original`, removes the extracted streams from the file (image-based subtitles are kept). |
| Convert subtitle | `.ass`/`.ssa`/`.vtt` file without an `.srt` of the same name | Writes `<name>.srt`; with `replace_original`, deletes the source. Never overwrites an existing SRT. |

All scripts write to `*.tmp.*` files first and clean them up when terminated.

---

## Processing Control

### Execution actions (`ExecutionControl`)

Actions only change the database; the worker running the execution notices within a second.

| Action | From | To |
|---|---|---|
| Pause | processing | paused (process tree SIGSTOPped) |
| Resume | paused | processing (SIGCONT) |
| Stop | queued / processing / paused | stopped (process tree terminated; queued jobs are skipped) |
| Retry | failed / stopped | queued (same record reset and re-dispatched) |
| Start | paused → resume; failed/stopped → retry | |
| Delete | any | row deleted; a running process is terminated |

### Processing gate (`ProcessingGate`)

Processing is held while **any** of these apply:

- **manual** — "Pause all" on the Workers page or `POST /api/v1/processing/pause`
- **streams** — `StreamTracker` has active playback sessions from media server webhooks. Sessions are keyed by session id, and they expire after the stream timeout (default 240 min) in case a stop event is missed.
- **schedule** — outside the daily processing window (`APP_TIMEZONE`, may wrap midnight)

While held, running processes are suspended and queued executions wait before starting.

### Crash recovery

`executions:reap` (every minute) fails processing/paused executions without a heartbeat for 5 minutes, e.g. after a container restart. `scan:libraries` resets libraries stuck in `scanning` for 5 minutes.

---

## Queues & Workers

| Queue | Consumer (supervisord program) | Jobs |
|---|---|---|
| `orchestrate-workers`, `notifications` | `orchestrator` (always on) | OrchestrateWorkers, SendExecutionNotification |
| `scan-libraries` | `scanner` (always on) | ScanLibrary |
| `transcode-media` | `Transcoder_00…09` | TranscodeMedia |
| `extract-subtitles` | `ExtractSubs_00…09` | ExtractSubtitles |
| `convert-subtitle` | `ConvertSubs_00…09` | ConvertSubtitle |

- Media workers run with `--timeout=0 --tries=1`; the database queue's `retry_after` defaults to 24h, so a long transcode is never handed to a second worker.
- `OrchestrateWorkers` starts `concurrency` processes of a job type's program and stops the rest (`supervisorctl`). It runs at container start and whenever a worker's `enabled`/`concurrency` changes.
- `scheduler` runs `schedule:work` (scan:libraries, executions:reap).

---

## Integrations

### Media server webhooks (`/webhooks/{jellyfin,plex,emby}`)

CSRF-exempt, throttled, optionally protected by `MEDIA_SERVER_WEBHOOK_TOKEN` (`X-Flowarr-Token` header or `?token=`).

| Server | Start | Stop | Session key |
|---|---|---|---|
| Jellyfin (webhook plugin) | `NotificationType=PlaybackStart` / `Event=playback.start`; unpaused `PlaybackProgress` keeps it alive | `PlaybackStop` / `playback.stop`; paused progress | `PlaySessionId`, else `DeviceId:ItemId` |
| Plex (multipart `payload`) | `media.play`, `media.resume` | `media.pause`, `media.stop` | `Player.uuid:Metadata.ratingKey` |
| Emby | `playback.start`, `playback.unpause` | `playback.stop`, `playback.pause` | `Session.Id` |

### Notifications

When enabled under Config → Processing, finished executions POST JSON to a webhook URL with `text`/`content`/`message` (Slack, Discord and bridges) plus a structured `execution` object.

### REST API (`/api/v1`)

Bearer token (or `X-Api-Key`) generated under Config → Processing; only its SHA-256 hash is stored.

| Method | Path |
|---|---|
| GET | `/status` — gate state, execution counts per status, workers |
| POST | `/processing/pause`, `/processing/resume` |
| GET | `/libraries`, `/libraries/{id}` |
| POST | `/libraries/{id}/scan` (202) |
| GET | `/executions` (`status`, `library_id`, `sort`, `direction`, `per_page`), `/executions/{id}` |
| POST | `/executions/{id}/{retry,pause,resume,stop}` (409 when the action does not apply) |

### Hardware detection

`HardwareCapabilities` probes `ffmpeg -encoders`, `nvidia-smi` and the VAAPI device, and reports the encoder transcodes will use (same selection logic as `transcode_media.sh`). It is cached for an hour, shown on the Workers page and can be re-detected.

---

## Web UI

| Page | Contents |
|---|---|
| Dashboard | Metrics, hold banner, live progress of running executions, recent executions, library health (polls every 5s) |
| Libraries | CRUD, directory browser, per-library workers, scan now, execution counts |
| Executions | Filter by status/library, search by path, progress, bulk retry/pause/resume/stop/delete; detail page with live log, timings and controls |
| Workers | Processing state + pause/resume/stop all, active streams, hardware acceleration, per-worker enable/concurrency/replace original and live process counts |
| Config → Processing | Processing window, stream timeout, notifications (with test), webhook URLs, API token |
| Config → Scan | Libraries scanned per scheduler tick |

---

## Configuration

| Env | Default | Purpose |
|---|---|---|
| `APP_TIMEZONE` | `UTC` | Timezone of the processing window |
| `ENABLE_GPU_TRANSCODING` | `true` | `false` forces libx265 |
| `TRANSCODE_HW_MODE` | `auto` | `auto`, `nvidia`, `amd` (VAAPI) or `software` |
| `VAAPI_DEVICE` | `/dev/dri/renderD128` | VAAPI render node |
| `FFMPEG_VIDEO_FILTER` | — | Overrides the automatic HDR tonemap filter |
| `MEDIA_SERVER_WEBHOOK_TOKEN` | — (falls back to `JELLYFIN_WEBHOOK_TOKEN`) | Shared secret for playback webhooks |
| `DB_QUEUE_RETRY_AFTER` | `86400` | Must exceed the longest transcode |
