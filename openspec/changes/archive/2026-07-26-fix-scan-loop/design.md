## Context

The scanning spec says `scan:libraries` SHALL "query all libraries where `status` is not paused/stopped and `(last_scan + scan_interval) < now()`". The current `dueForScan` scope narrows this to `PENDING_SCAN` only — a subset of what the spec describes. After a scan completes, libraries are restored to `PENDING` but never transition back to `PENDING_SCAN`, so the auto-scan loop dead-ends.

The state machine has five states (`PENDING`, `PENDING_SCAN`, `SCANNING`, `PAUSED`, `STOPPED`) but only `PENDING_SCAN` triggers scanning. Nothing writes `PENDING_SCAN` except a manual UI trigger.

## Goals / Non-Goals

**Goals:**
- Libraries automatically re-scan on their configured `scan_interval` without manual intervention
- Libraries scan on first creation without requiring manual trigger
- Manual "scan now" button continues to work via `PENDING_SCAN`
- Align implementation with the existing scanning spec

**Non-Goals:**
- Changing the scan interval scheduling mechanism
- Adding scan history, progress tracking, or notifications
- Modifying the scanning logic (what gets scanned, job dispatch)
- Adding checksum-based dedup

## Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Scope fix | `dueForScan` includes both `PENDING` and `PENDING_SCAN`. Time interval check only applies to `PENDING`. `PENDING_SCAN` bypasses it. | `PENDING` libraries respect `last_scan + scan_interval`. `PENDING_SCAN` (manual trigger) always runs immediately — otherwise clicking "Scan Now" on a recently-scanned library does nothing. |
| Initial status | `PENDING_SCAN` on creation instead of `PENDING` | First scan shouldn't require manual trigger. The time condition (`last_scan IS NULL`) ensures it's picked up immediately. |
| Scan reset | After scan, status → `PENDING` (unchanged) | Next interval check uses `last_scan + scan_interval`. Status in `PENDING` is now matched by the scope. |
| `PAUSED`/`STOPPED` | Explicitly excluded (unchanged) | These statuses are already excluded by not being in the `whereIn` list. |

## Risks / Trade-offs

- **Race condition on initial scan**: If a library is created as `PENDING_SCAN` and the scanner runs before the user configures jobs, it finds nothing (library has no `libraryJobs` yet, so `has('libraryJobs')` in `dueForScan` excludes it). Low risk, safe.
- **Frequent re-scans**: Libraries in `PENDING` with very short `scan_interval` get re-scanned every cycle. Mitigation: `scan_interval` minimum is 60 seconds (validated in form requests), and the scheduler runs every minute anyway.
- **Manual trigger still works**: `LibrariesController::triggerScan` sets `PENDING_SCAN` — this status is included in the scope, so it's picked up on next scheduler tick.
