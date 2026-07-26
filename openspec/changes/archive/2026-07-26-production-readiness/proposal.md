## Why

Flowarr has a solid architectural foundation with working auth, library management, queue jobs, and Jellyfin integration — but the project metadata, documentation, CI, and consistency gaps make it unfit for public open-source release. The package still carries the Laravel starter-kit identity, many files say "non-functional" when they're built, and core community standards (LICENSE, contributing guide, issue templates) are missing entirely.

## What Changes

- Set project identity — fix `composer.json` name, add `LICENSE` file, update app name
- Add open-source community files — `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, `SECURITY.md`
- Add GitHub issue & PR templates — bug report, feature request, pull request
- Update `README.md` to reflect current state (it says "non-functional" but auth, UI, jobs, webhooks all exist)
- Harden CI — ensure lint, static analysis, and test workflows pass reliably
- Fix all PHPStan level 7 errors and TypeScript strict-mode errors exposed by CI
- Fix naming inconsistency — `ConvertSubtitleJob` references `ProcessSubtitleJob` in error messages
- Add `ScanLibraries` scheduled command so the core loop (scan → dispatch → execute) works end-to-end
- Fill critical test gaps — settings controllers, auth pages, scan command
- Normalize `.env.example` to match production defaults

## Capabilities

### New Capabilities

- `project-identity`: Project metadata, branding, naming conventions
- `community-standards`: Open-source governance files (LICENSE, contributing, code of conduct, security, issue/PR templates)
- `ci-pipeline`: Build and test pipeline including static analysis, formatting, and test reporting

### Modified Capabilities

- `management-ui`: README updated to reflect actual UI state
- `queue-infrastructure`: Queue routing configuration added for per-job-type queues
- `scanning`: Scanner service completed with end-to-end execution creation

## Impact

- `composer.json` — fix `name`, `description`, add `keywords`
- Root — add `LICENSE`, `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, `SECURITY.md`
- `.github/` — add `ISSUE_TEMPLATE/bug.yml`, `ISSUE_TEMPLATE/feature.yml`, `PULL_REQUEST_TEMPLATE.md`
- `.env.example` — sync with actual `.env` defaults
- `app/Jobs/ConvertSubtitleJob.php` — fix `ProcessSubtitleJob` references to `ConvertSubtitleJob`
- `app/Console/Commands/` — create `ScanLibraries` command
- `app/Services/` — ensure `ScannerService` creates `Execution` records
- `tests/` — add tests for settings controllers, scan command, auth flows
- `README.md` — complete rewrite to reflect actual feature state
- `.github/workflows/` — fix any CI issues discovered during testing
