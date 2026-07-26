## Why

Vitest + Testing Library can verify component logic, but they render in jsdom — no real browser, no CSS, no layout. They can't catch visual regressions, layout shifts, broken Tailwind classes, wrong colors, overlapping elements, or Inertia page transition glitches. AI agents need a real browser to look at the UI, take screenshots, and spot the same issues a human would see.

## What Changes

- Add Playwright as a dev dependency with Chromium browser
- Add a `tests-browser/` directory for Playwright tests
- Add a `composer run test:browser` script that starts Sail, runs migrations, starts Vite, and runs Playwright
- Configure Playwright to connect to the Sail application container
- Add visual regression testing with screenshot baselines stored in the repo
- Add smoke tests for each page (loads without JS errors, renders key elements)
- Agents run Playwright locally during `/opsx-apply` to verify every UI change

## Capabilities

### New Capabilities

- `browser-testing`: Playwright-based browser testing with visual regression, page smoke tests, and AI-agent-friendly test runner

### Modified Capabilities

- `management-ui`: Each UI page gets a smoke test that verifies it renders without errors

## Impact

- `package.json` — add `@playwright/test` dev dependency
- `playwright.config.ts` — new Playwright config pointing at `http://localhost` (Sail)
- `tests-browser/` — new directory for Playwright tests
- `tests-browser/smoke/` — smoke tests for each page (public, auth, dashboard, libraries, executions, workers, settings)
- `tests-browser/visual/` — visual regression tests with screenshot baselines
- `tests-browser/pages/` — page object models for reusable selectors
- `.gitignore` — add Playwright browser cache, exclude test artifacts
