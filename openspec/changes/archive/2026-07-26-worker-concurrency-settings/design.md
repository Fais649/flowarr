## Context

Currently there's no mechanism to limit how many jobs of a given type run concurrently. Workers pick jobs off the queue and run them — if five `queue:work` processes are running and all pick transcode jobs, five ffmpeg processes compete for the GPU. Users need to tune this per job type from the UI.

The existing polling loop pattern (used by TranscodeMediaJob and ConvertSubtitleJob) already supports pause via `shouldPause()` checking `active_streams` and `media_processing_paused` cache keys. The concurrency check slots naturally into the same entry-point pattern — before starting the subprocess, check if we're at capacity.

## Goals / Non-Goals

**Goals:**
- `settings` DB table for key-value storage
- Concurrency limit per job type (transcode, extract subs, convert subs)
- Jobs check limit at start — release back to queue if at capacity
- Worker settings UI page
- Shared `shouldPause()` + concurrency logic extracted for consistency
- Existing pause behavior preserved

**Non-Goals:**
- Dynamic worker process management (no daemon, no supervisor config generation)
- Auto-scaling or adaptive concurrency
- Per-library or per-user limits

## Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Settings storage | Dedicated `settings` table (key, value) | Simpler than config files for UI-writable values. No env var bouncing. |
| Concurrency check mechanism | Query `Execution` where status=processing + matching job type, compare to limit | Uses existing Execution model. No new state to track. Naturally counts paused jobs (SIGSTOP'd but still processing). |
| Release delay | `$this->release(30)` — 30 second delay before retry | Prevents tight re-pickup loops. Matches typical ffmpeg job duration granularity. |
| Shared job logic | Extract `shouldPause()` + concurrency check into a trait or helper | Avoids duplicating the same check across three jobs. Single source of truth. |
| Default limits | Transcode: 1, Extract subs: 4, Convert subs: 4 | GPU-bound vs CPU-light heuristic. Users can tune from UI. |
| Settings page route | `GET /settings/workers` under the `auth,verified` middleware group | Follows existing settings route pattern (`settings/profile`, `settings/security`, `settings/appearance`). |

## Risks / Trade-offs

- **Release-back means job ordering isn't guaranteed** — jobs that release may be picked up after jobs that were dispatched later. Acceptable — concurrency limits inherently trade ordering for throughput.
- **Counting `processing` Execution status works but is approximate** — if a job crashes without updating status to `failed`, that execution stays `processing` forever, eating a slot. Mitigation: add a heartbeat or timeout-based auto-fail mechanism in the future. For now, users can manually cancel stuck executions from the UI.
