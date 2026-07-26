## Context

Queue workers started via `artisan dev` run `queue:listen --tries=1 --timeout=0` which only listens to the `default` queue. Jobs are dispatched to named queues (`transcode`, `subtitle`) via `LibraryJobId::getQueue()`. Result: executions are created with status `queued`, jobs sit in named queues unprocessed, pipelines never complete. Libraries stay `pending_scan` because the scanner doesn't re-trigger (or the scan hangs mid-way).

The dashboard shows basic metric cards but no worker visibility. Executions table has cancel icon JS logic but no route. Test data doesn't exist for pipeline testing.

## Goals / Non-Goals

**Goals:**
- Queue worker in dev listens to all job queues so jobs actually process
- Dashboard shows worker status (idle/processing, active job count, last heartbeat)
- Executions can be cancelled/aborted from the table
- Fix libraries stuck in `pending_scan`
- `../test-data/` folder with realistic `.mkv` + subtitle files and restore script
- Debug-only button in UI to restore test data

**Non-Goals:**
- Worker heartbeat mechanism or registration (Phase 4 in ARCHITECTURE.md)
- Real-time worker monitoring via websockets
- Queue metrics (depth, latency) on dashboard
- Production SQS/RabbitMQ configuration
- Test data generation script (files are pre-created)

## Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Queue fix | `--queue=transcode,subtitle,default` on `queue:listen` | Simplest fix. Workers pick up jobs from all queues in order. Each queue gets fair processing. |
| Execution cancel | POST route `/executions/{id}/cancel` → status→`stopped` | Uses existing `ExecutionStatus::STOPPED`. The running job checks status before each ffmpeg call and self-terminates. |
| Worker status on dashboard | Query `Execution` for currently processing → group by worker_id | No Worker model heartbeat yet. Shows what's currently running. Practical middle ground. |
| Stuck pending_scan | Check if `dueForScan` scope has `has('libraryJobs')` filter preventing pickup when jobs are disabled or missing | Root cause might be libraries without any libraryJobs configured. |
| Test data restore script | Bash `restore-test-data.sh` that copies from `.bak` tar to `test-data/` | Simple, no dependencies. Mounted at `/media` in Docker. |
| Debug button visibility | Check `app()->isLocal()` or `APP_DEBUG=true` env var on backend, pass to frontend via Inertia shared data | Only renders in dev, no risk in production. |

## Risks / Trade-offs

- **Queue order**: `--queue=transcode,subtitle,default` means transcode always gets priority. Risk: subtitle jobs starve if transcode is saturated. Mitigation: add concurrency limits per queue via separate workers.
- **Cancel endpoint**: Setting status to `stopped` doesn't kill a running ffmpeg process. The job needs to check status periodically. Risk: jobs continue until they check. Mitigation: jobs check execution status before each probe/dispatch step.
- **Libraries stuck pending_scan**: Could be `last_scan` timestamp past interval but `PENDING_SCAN` status never set. The previous fix added `PENDING` to `dueForScan` scope — verify deployed.
