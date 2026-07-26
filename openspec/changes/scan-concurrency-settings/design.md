## Context

`ScanLibraries::handle()` processes libraries one at a time in a `foreach` loop. With multiple libraries each scanning hundreds/thousands of files, a single long scan blocks others from starting until the next schedule tick (1 minute later). The current 1-hour default scan interval is also too aggressive for a media library that rarely changes.

There's already a `Settings` helper class and `Setting` model for key-value configuration, plus a `WorkerSettingsController` that follows the pattern of: settings page → form request → `Setting::set()` → read via `Settings::get()`. This change extends that same pattern for scan configuration.

## Goals / Non-Goals

**Goals:**
- Allow users to configure how many libraries scan in parallel via a UI setting
- `ScanLibraries` command runs concurrent library scans up to the configured limit
- Change default `scan_interval` on new libraries from 1 hour to 12 hours
- Add a settings page under Settings → Scan (matching worker settings pattern)

**Non-Goals:**
- Parallelizing within a single library scan (files are still scanned sequentially per library)
- Queue-based scan dispatching (scan remains a scheduled command)
- Dynamic concurrency based on system load
- Per-library scan concurrency (the setting is global)

## Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Parallel mechanism | `Illuminate\Support\Facades\Concurrency::run()` with `fork` driver | Built into Laravel 13. Uses `spatie/fork` internally. Each fork is an isolated PHP process — database connections handled automatically. No new dependencies. |
| Setting key pattern | `scan.concurrency` → `Settings::scanConcurrency()` | Matches existing `concurrency.{$jobType}` pattern used by worker settings. |
| Default concurrency | 2 | Safe default. Most self-hosted setups have 2-4 cores. User can raise or lower. |
| Default scan interval | 43200s (12 hours) | Media libraries on private servers change infrequently. 12h is standard for *arr apps. |
| UI placement | Settings → Scan (new page) | Follows existing `WorkerSettingsController` pattern. Route: `settings/scan`. |
| Settings scope | Global (not per-library) | Scan workers are a system-level concern, matching how worker concurrency is configured. |

## Risks / Trade-offs

- **Process fork overhead**: Forking for each library scan has memory overhead. Mitigation: concurrency defaults to 2, and scanning one additional library in parallel is worth the fork cost.
- **Race conditions on Execution table**: Two concurrent library scans could process the same file if libraries share paths. Mitigation: `hasExistingExecution()` checks already prevent duplicate queued/processing executions per file.
- **Long scans blocking schedule**: If a scan takes longer than 1 minute, the library stays in `SCANNING` status and won't be re-picked up. The concurrency limit ensures at most N libraries scan simultaneously.
- **Fork not available on Windows**: Not a concern — production runs on Linux (Docker).
