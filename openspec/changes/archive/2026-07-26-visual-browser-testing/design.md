## Context

Current frontend testing uses Vitest + Testing Library with jsdom — no real browser, no CSS rendering, no screenshot capability. AI agents can write assertions about component behavior but can't verify that a page actually looks right or that Inertia page transitions don't break rendering. The app uses React 19 + Inertia v3 + Tailwind v4 + shadcn/ui inside a Laravel Sail Docker environment.

## Goals / Non-Goals

**Goals:**
- Playwright with Chromium as the browser testing framework
- Smoke tests for every page (public, auth, authenticated)
- Visual regression testing via screenshot comparison
- AI-agent-friendly output (exit codes, HTML report, failure screenshots)
- Page object models for reusable selectors
- Agents run Playwright locally during `/opsx-apply` to verify every UI change

**Non-Goals:**
- Replacing Vitest component tests (they serve different purposes)
- Cross-browser testing (Chromium only for now — Firefox/WebKit can be added later)
- End-to-end job execution testing (running actual ffmpeg pipelines)
- Performance or load testing
- CI integration (local-only, used by agents during implementation)

## Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Test framework | `@playwright/test` | Industry standard for browser testing. Visual regression is built in. Trace viewer for debugging failures. AI agents can write/run tests easily. |
| Browser | Chromium only (default) | Single browser keeps runs fast. Firefox and WebKit can be opt-in later if cross-browser issues emerge. |
| Test directory | `tests-browser/` | Separate from `tests/` (PHP Pest) and `tests-frontend/` (Vitest). Clear namespace. |
| Visual regression approach | Playwright built-in `expect(page).toHaveScreenshot()` | Snapshot comparison with pixel-diff threshold. Baselines checked into repo. Updates via `--update-snapshots`. |
| App connection | Playwright connects to `http://localhost` which maps to Sail's nginx | Sail already exposes port 80. No extra network config needed. |
| Test data | Dedicated `phpunit` database with `DatabaseSeeder` that includes test user + library + executions | Same pattern as existing Pest tests. `php artisan migrate --seed` before Playwright runs. |
| Run timing | Playwright tests run locally during `/opsx-apply` as a mandatory step of every UI change | The agent sees test results immediately during implementation — no round-trip. |
| Headless mode | Tests run headless by default, can toggle via `--headed` for debugging | Headless keeps the agent workflow clean. `--headed` useful when an agent needs to visually inspect a specific state. |
| UI change mandate | Every UI change SHALL include or update a Playwright smoke test for the affected pages | Ensures agents always verify UI changes in a real browser. Enforced at the task level — the change's tasks.md must include the test work. |

## Risks / Trade-offs

- **Sail startup time** — Starting the full Docker stack takes 1-2 minutes. Runs every `/opsx-apply` that touches the UI. Acceptable — the guarantee that every UI change is verified in a real browser before it ships is worth the wait.
- **Screenshot baseline churn** — UI changes will break visual regression tests until baselines are updated. Mitigation: document the `--update-snapshots` workflow. Use loose thresholds (0.1-0.2%) to avoid flaky diffs from anti-aliasing.
- **Flaky tests from Inertia page transitions** — Deferred props or loading states could cause timing issues. Mitigation: use `waitForSelector` and `waitForResponse` patterns in page objects, not bare `page.goto` + assert.
- **Agent writes test, runs it, sees screenshot** — The trace viewer and HTML report give agents everything they need to debug failures without a manual browser session.
