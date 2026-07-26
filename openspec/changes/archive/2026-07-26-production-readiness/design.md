## Context

Flowarr was scaffolded from the Laravel React starter kit and has accumulated a solid foundation — auth via Fortify, library CRUD, three ffmpeg/mkvmerge queue jobs, Jellyfin webhook integration, and a React/Inertia UI. However, the project still carries the starter-kit's identity, lacks community-standard files, has CI that may not pass cleanly, and has a gap in the core loop: jobs receive raw file paths instead of execution IDs, so the Execution model is never updated. For open-source release, the project needs a coherent identity, proper governance files, a trustworthy CI badge, and a working end-to-end pipeline.

## Goals / Non-Goals

**Goals:**
- Establish project identity (package name, app name, LICENSE)
- Add community governance files (CONTRIBUTING, CODE_OF_CONDUCT, SECURITY)
- Add GitHub issue/PR templates for contributor experience
- Update README to reflect actual functionality
- Fix CI so lint, static analysis, and tests pass reliably
- Fix `ProcessSubtitleJob` naming left-over in ConvertSubtitleJob
- Add `ScanLibraries` command with scheduler registration
- Add queue routing config and job-to-queue mapping on `LibraryJobId`
- Wire jobs to update `Execution` records (status, started_at, finished_at)
- Add critical missing tests

**Non-Goals:**
- New job types or job features (e.g., transcode quality presets, subtitle format options)
- Worker heartbeat or capability detection (Phase 4 in ARCHITECTURE.md)
- API versioning or dedicated API routes
- Frontend component testing (Vitest — separate change exists)
- Docker image publishing or deployment packaging
- i18n/localization

## Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Package name | `flowarr/flowarr` | Matches the project's identity. Simple, memorable, follows packagist convention. |
| License | MIT | Already declared in composer.json. Standard for open-source Laravel projects. No reason to change. |
| Scanning schedule | Every 1 minute via Laravel scheduler | Standard Laravel pattern. `schedule->command('scan:libraries')->everyMinute()` in `routes/console.php`. The scan itself is idempotent — it respects `last_scan + scan_interval`. |
| Execution wiring | Pass `executionId` to job constructor instead of raw `filePath` | Jobs already accept `string $filePath`. Add optional `?int $executionId` parameter. When present, job updates the Execution record at each lifecycle event. Backward-compatible: existing dispatches without executionId continue to work. |
| Queue routing | `LibraryJobId::getQueue()` returns queue name from config | Follows existing `getJobClass()` pattern on the enum. Queue names are configurable via `config('queue.queues.<name>')`. Each job class calls `$this->onConnection('rabbitmq')->onQueue($queue)` in its constructor. |
| CI fix approach | Run CI locally via Sail, fix errors iteratively | Safer than guessing. Run `vendor/bin/sail artisan test`, `vendor/bin/sail bin pint --test`, `vendor/bin/sail bash -c './vendor/bin/phpstan'`, `npm run types:check` and fix each failure. |
| README rewrite | Full rewrite with accurate sections | The current README says "non-functional" which is misleading. Replace with proper: description, feature list, quickstart, tech stack, contributing link, CI badge. |
| Community files | Manually written (not generated) | CONTRIBUTING.md, CODE_OF_CONDUCT.md, SECURITY.md are standard boilerplate — no generator needed. Use Contributor Covenant 2.1 for CoC. |

## Risks / Trade-offs

- **Execution wiring is additive** — Jobs currently work with raw file paths. Adding executionId as optional means both code paths coexist. Risk of confusion → Mitigation: make executionId required in the scan dispatch path, optional only for backward compat during transition.
- **CI may surface latent issues** — PHPStan level 7 and TypeScript strict mode may have errors that weren't visible before. Mitigation: fix them as part of this change. If blockers exist, document them and adjust CI to known-passing state.
- **Queue routing changes are noisy** — Each job class needs `onQueue()` call. Low risk but touch three files. Mitigation: grouped in a single commit.
- **Scan command without file dedup** — The current scanning spec says "skip files with pending/processing executions" but doesn't have checksum-based dedup. Acceptable for initial release — the simple dedup (check for existing queued executions) prevents duplicate work.
