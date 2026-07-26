## 1. Playwright Setup

- [x] 1.1 Install Playwright: `npx playwright install chromium` and add `@playwright/test` to `package.json` devDependencies
- [x] 1.2 Create `playwright.config.ts` with base URL `http://localhost`, test dir `tests-browser/`, reporter config, and screenshot settings
- [x] 1.3 Add test scripts to `package.json`: `"test:browser": "npx playwright test"` and `"pretest:browser"` seeder script
- [x] 1.4 Create `BrowserTestSeeder` for test user, library with jobs, and execution
- [x] 1.5 Verify Chromium launches and connects to the Sail app (global-setup + connectivity test pass)

## 2. Test Data & Helpers

- [x] 2.1 Create `tests-browser/fixtures/` directory with `BrowserTestSeeder` and `global-setup.ts`
- [x] 2.2 Create `tests-browser/helpers/auth.ts` — login helper and `loginAndSaveState` for storage state
- [x] 2.3 Create `tests-browser/pages/` page object models for login, dashboard, libraries, executions, workers

## 3. Smoke Tests

- [x] 3.1 `tests-browser/smoke/welcome.spec.ts` — welcome page loads without console errors
- [x] 3.2 `tests-browser/smoke/auth.spec.ts` — login, register, forgot-password pages render
- [x] 3.3 `tests-browser/smoke/dashboard.spec.ts` — dashboard loads without console errors, sidebar shows nav sections
- [x] 3.4 `tests-browser/smoke/libraries.spec.ts` — libraries index and create pages render
- [x] 3.5 `tests-browser/smoke/executions.spec.ts` — executions index renders
- [x] 3.6 `tests-browser/smoke/workers.spec.ts` — workers index renders
- [x] 3.7 `tests-browser/smoke/settings.spec.ts` — profile, security, appearance settings render

## 4. Visual Regression Tests

- [x] 4.1 `tests-browser/visual/welcome.spec.ts` — welcome page screenshot baseline
- [x] 4.2 `tests-browser/visual/auth-pages.spec.ts` — login, forgot-password screenshot baselines
- [x] 4.3 `tests-browser/visual/dashboard.spec.ts` — dashboard screenshot baseline
- [x] 4.4 `tests-browser/visual/libraries.spec.ts` — libraries index and create screenshot baselines
- [x] 4.5 `tests-browser/visual/executions.spec.ts` — executions index screenshot baseline
- [x] 4.6 Run `--update-snapshots` to generate all baselines (8 visual tests pass)

