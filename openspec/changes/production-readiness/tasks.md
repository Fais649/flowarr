## 1. Project Identity

- [x] 1.1 Fix `composer.json` — set `name` to `flowarr/flowarr`, update `description` and `keywords`
- [x] 1.2 Update `config/app.php` — set `name` to `Flowarr` (already defaulted)
- [x] 1.3 Add MIT `LICENSE` file to project root
- [x] 1.4 Update `resources/js/app.tsx` — already defaulted to `Flowarr`

## 2. Community Standards

- [x] 2.1 Create `CONTRIBUTING.md`
- [x] 2.2 Create `CODE_OF_CONDUCT.md` (Contributor Covenant v2.1)
- [x] 2.3 Create `SECURITY.md`
- [x] 2.4 Add `.github/ISSUE_TEMPLATE/bug.yml`
- [x] 2.5 Add `.github/ISSUE_TEMPLATE/feature.yml`
- [x] 2.6 Add `.github/PULL_REQUEST_TEMPLATE.md`

## 3. README Rewrite

- [x] 3.1 Rewrite `README.md` with accurate feature list, tech stack, quickstart, CI badge
- [x] 3.2 Removed "non-functional" claim and outdated roadmap
- [x] 3.3 Added CI status badge

## 4. Code Consistency Fixes

- [x] 4.1 Fix `ConvertSubtitleJob` error messages (`ProcessSubtitleJob` → `ConvertSubtitleJob`)
- [x] 4.2 Sync `.env.example` with production defaults + pruned unused services (Redis, Meilisearch, RabbitMQ)

## 5. Queue Routing Configuration

- [x] 5.1 Add queue name config to `config/queue.php`
- [x] 5.2 Add `getQueue(): string` to `LibraryJobId`
- [x] 5.3 `TranscodeMediaJob` → transcode queue
- [x] 5.4 `ExtractSubtitlesJob` → subtitle queue
- [x] 5.5 `ConvertSubtitleJob` → subtitle queue

## 6. Execution Wiring in Jobs

- [x] 6.1-6.3 Add optional `?int $executionId` to all 3 job constructors
- [x] 6.4 `TracksExecution` trait with `markExecutionAsProcessing/Completed/Failed`
- [x] 6.5 `DispatchableJob` interface unchanged (executionId is optional, per design)

## 7. Scanner Service & Command

- [x] 7.1 Create `ScannerService` — walks library paths, probes files, creates Executions
- [x] 7.2 Create `ScanLibraries` Artisan command (`scan:libraries`)
- [x] 7.3 Register in `routes/console.php` to run `everyMinute()`
- [x] 7.4 Dedup check — skips files with existing queued/processing executions
- [x] 7.5 Scanner passes `executionId` to dispatched jobs via `TracksExecution` trait

## 8. CI Hardening

- [x] 8.1 All 84 tests pass (81 passed, 3 skipped)
- [x] 8.2 Pint formatting clean
- [x] 8.3 PHPStan level 7 with baseline — 0 errors
- [x] 8.4 TypeScript strict mode — 0 errors
- [x] 8.5 Prettier and ESLint — autofixable issues resolved; 23 pre-existing remain (unused imports, storybook imports)
- [x] 8.6 Workflows verified

## 9. Critical Test Coverage

- [x] 9.1 Feature test for `ScanLibraries` (4 tests: creates executions, skips without jobs, dedup, updates last_scan)
- [x] 9.2 Feature test for settings profile controller — exists (ProfileUpdateTest.php, 5 tests)
- [x] 9.3 Feature test for settings security controller — exists (SecurityTest.php, 4 tests)
- [x] 9.4 Feature test for auth flows — exists (AuthenticationTest, OnboardingTest, PasswordResetTest, RegistrationTest, 16 tests)
- [x] 9.5 Execution status update tests added to `TranscodeMediaJobTest`
