# Browser Testing

## Purpose

Provide Playwright-based browser testing infrastructure for smoke testing, visual regression testing, and automated UI verification.

## Requirements

### Requirement: Playwright Setup
The system SHALL use Playwright as the browser testing framework with Chromium as the default browser.

#### Scenario: Playwright configured
- **WHEN** running `npx playwright test`
- **THEN** tests execute against a real Chromium browser
- **THEN** the browser connects to the Laravel Sail application at `http://localhost`

### Requirement: Page Smoke Tests
Every application page SHALL have a smoke test that verifies it loads without JavaScript errors and renders key elements.

#### Scenario: Public pages load
- **WHEN** Playwright visits the welcome page
- **THEN** the page loads without console errors
- **THEN** the page title or heading contains "Flowarr"

#### Scenario: Auth pages load
- **WHEN** Playwright visits the login page
- **THEN** the login form is visible
- **WHEN** Playwright visits the register page
- **THEN** the registration form is visible
- **WHEN** Playwright visits the forgot-password page
- **THEN** the email input is visible

#### Scenario: Authenticated pages render
- **WHEN** Playwright logs in as a test user
- **THEN** the dashboard loads without console errors
- **THEN** sidebar navigation items are visible: Dashboard, Libraries, Executions, Workers
- **WHEN** Playwright navigates to /libraries
- **THEN** the libraries page renders
- **WHEN** Playwright navigates to /executions
- **THEN** the executions page renders
- **WHEN** Playwright navigates to /workers
- **THEN** the workers page renders

### Requirement: Visual Regression Testing
The system SHALL support screenshot-based visual regression testing to detect visual regressions.

#### Scenario: Visual comparison
- **WHEN** Playwright takes a full-page screenshot of a page
- **THEN** it is compared against a stored baseline image
- **THEN** if the difference exceeds a threshold, the test fails
- **WHEN** a failing snapshot is reviewed and the change is intentional
- **THEN** the baseline can be updated by running tests with `--update-snapshots`

#### Scenario: Baseline storage
- **WHEN** visual regression tests pass for the first time
- **THEN** baseline screenshots are stored in the repository under `tests-browser/visual/__snapshots__/`
- **WHEN** baselines exist and a test passes
- **THEN** no diff or updated snapshot is produced

### Requirement: AI-Agent-Friendly Output
Playwright tests SHALL produce output that AI agents can parse to determine pass/fail and investigate failures.

#### Scenario: Structured output
- **WHEN** tests pass
- **THEN** the exit code is 0
- **WHEN** tests fail
- **THEN** the exit code is 1
- **THEN** a detailed HTML report is generated in `playwright-report/`
- **THEN** failed test screenshots and trace files are saved for inspection

### Requirement: Test Database Seeding
Browser tests SHALL run against a test database seeded with known data (a test user, a library, some executions).

#### Scenario: Test data available
- **WHEN** the browser test suite runs
- **THEN** a test user exists with known credentials
- **THEN** at least one library exists with jobs configured
- **THEN** at least one execution exists with a known status

### Requirement: UI Change Acceptance
Any change that adds, modifies, or removes a UI page SHALL run the relevant Playwright tests during implementation, before the change is marked complete.

#### Scenario: Test run during implementation
- **WHEN** an agent implements a change that touches a UI page (via `/opsx-apply`)
- **THEN** the change's tasks SHALL include running the Playwright smoke test for the affected page
- **THEN** the agent SHALL verify the test passes before marking the change complete

#### Scenario: New page requires new smoke test
- **WHEN** a change introduces a new page
- **THEN** the change's tasks SHALL include creating a Playwright smoke test that visits the new page and asserts it renders without console errors

#### Scenario: Modified page requires updated test
- **WHEN** a change modifies an existing page's structure, layout, or behavior
- **THEN** the change's tasks SHALL include updating the existing Playwright smoke test to reflect the new state

#### Scenario: Visual change requires baseline update
- **WHEN** a change alters the visual appearance of a page that has a visual regression test
- **THEN** the change's tasks SHALL include updating the visual regression baseline via `--update-snapshots`

#### Scenario: Change blocked on test failure
- **WHEN** a Playwright test fails during implementation
- **THEN** the agent SHALL investigate the failure (console errors, layout breakage, visual diff)
- **THEN** the agent SHALL fix the underlying issue before marking the change complete
