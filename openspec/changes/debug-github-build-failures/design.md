## Context

Three GitHub Actions workflows: `tests.yml`, `linter.yml`, `chromatic.yml`. Linter passes. Tests and Chromatic fail consistently after commit `766dafb` which attempted fixes for boot crash, eslint, lockfile, and phpstan issues.

**Tests failure — root causes identified via CI logs:**
1. **PHP 8.3 matrix entry incompatible**: `symfony/error-handler v8.1.0` requires `php >=8.4.1`. Lockfile deps drifted above the declared `^8.3` constraint. Fix: remove 8.3 from matrix or lock to older compat versions.
2. **Build timeout**: `npm i` + `composer install` + `npm run build` + `npm run build-storybook` + `npm test` + `php artisan test` in a single job exceeds GitHub's default 6-hour timeout for higher matrix entries (observed cancellation on 8.4 and 8.5).
3. **Uses `npm i` instead of `npm ci`**: `npm i` respects lockfile but can modify it; `npm ci` is the correct deterministic install for CI.
4. **Uses `npm run build` twice** (once for vite, once for Storybook) — Storybook build should be exclusive to Chromatic workflow.

**Chromatic failure:**
- `CHROMATIC_PROJECT_TOKEN` secret not set in repository. Action fails immediately with `Missing project token`.

## Goals / Non-Goals

**Goals:**
- Tests workflow passes on `main` for all matrix entries
- Linter workflow stays green
- Chromatic workflow fails gracefully when token missing (not with cryptic error)
- CI pipeline spec updated to reflect current PHP version requirements

**Non-Goals:**
- Adding new CI workflows or integrating new services
- Performance tuning beyond preventing timeouts
- Fixing application-level test failures (these should pass already)

## Decisions

1. **Remove PHP 8.3 from matrix, keep 8.4 and 8.5**
   - Alternative: downgrade symfony/error-handler and other deps to support 8.3
   - Rationale: Laravel 13+ ecosystem targets PHP 8.4+. Keeping 8.3 adds ongoing compatibility drag. The project's own `composer.json` requires `^8.3` but the resolved lockfile has already moved past that — cleanest fix is aligning the matrix with reality.

2. **Remove `build-storybook` from tests workflow**
   - Alternative: keep it and increase timeout
   - Rationale: Storybook build is already covered by the Chromatic workflow. Running it in tests duplicates work and contributes to timeouts. Tests should validate code, not visual output.

3. **Switch `npm i` to `npm ci` in tests workflow**
   - Rationale: `npm ci` is faster, stricter, and the standard for CI environments. Prevents accidental lockfile mutations.

4. **Chromatic: validate token before running action**
   - Add a step checking `${{ secrets.CHROMATIC_PROJECT_TOKEN }}` non-empty before proceeding. Fail with a clear message rather than the opaque "Missing project token" error.

5. **Add explicit timeout bounds to long-running steps**
   - Set `timeout-minutes` on the job level to fail fast instead of waiting for the default 360-min timeout.

## Risks / Trade-offs

- **Dropping PHP 8.3**: Unblocks CI. If a future dependency forces 8.3 compatibility, the matrix can be re-added. Low risk since the project is early-stage.
- **Removing storybook build from tests**: If Chromatic workflow breaks but tests pass, Storybook regressions go undetected. Mitigation: Chromatic is the canonical visual regression check.
- **Secret not set for Chromatic**: Non-zero exit code blocks PR merge even with graceful message. Mitigation: The token should be configured in repo secrets once; this change documents the requirement.
