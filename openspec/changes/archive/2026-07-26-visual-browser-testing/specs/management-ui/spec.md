## ADDED Requirements

### Requirement: Smoke Test Coverage
Each management UI page SHALL have a browser-level smoke test that verifies it renders without JavaScript errors.

#### Scenario: Dashboard smoke test
- **WHEN** an authenticated Playwright session visits /dashboard
- **THEN** the page loads with no console errors
- **THEN** metric cards or an empty state is visible

#### Scenario: Libraries page smoke test
- **WHEN** an authenticated Playwright session visits /libraries
- **THEN** the page loads with no console errors
- **THEN** the page shows either a library table or an empty state

#### Scenario: Executions page smoke test
- **WHEN** an authenticated Playwright session visits /executions
- **THEN** the page loads with no console errors
- **THEN** the filter bar and execution list or empty state is visible

#### Scenario: Workers page smoke test
- **WHEN** an authenticated Playwright session visits /workers
- **THEN** the page loads with no console errors
- **THEN** the workers list or empty state is visible

#### Scenario: Settings pages smoke test
- **WHEN** an authenticated Playwright session visits /settings/profile
- **THEN** the page loads with no console errors
- **THEN** the profile form fields are visible
- **WHEN** Playwright visits /settings/security
- **THEN** the security page loads without errors
- **WHEN** Playwright visits /settings/appearance
- **THEN** the appearance page loads without errors
