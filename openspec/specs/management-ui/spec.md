# Management UI

## Purpose

Provide a web interface for managing libraries, monitoring executions, and viewing workers.
## Requirements
### Requirement: Sidebar Navigation
The application SHALL provide sidebar navigation to all management sections.

#### Scenario: Navigation sections
- **WHEN** a user is authenticated
- **THEN** the sidebar shows: Dashboard, Libraries, Executions, Workers
- **THEN** the active section is highlighted

### Requirement: Empty States
Pages with no data SHALL display helpful empty states with a call to action.

#### Scenario: No libraries configured
- **WHEN** the libraries page has no records
- **THEN** an empty state is shown with a button to create the first library

#### Scenario: No executions
- **WHEN** the executions page has no records
- **THEN** a message explains that executions appear after libraries are configured and scanned

### Requirement: Clean Brand State
No Laravel starter-kit branding, logos, or references SHALL appear in the rendered UI.

#### Scenario: No Laravel references
- **WHEN** any page renders
- **THEN** no "Laravel", "Laracasts", "Laravel Starter Kit", or similar text appears in the UI

#### Scenario: Favicon files removed
- **WHEN** the application loads
- **THEN** `public/favicon.ico`, `public/favicon.svg`, and `public/apple-touch-icon.png` no longer exist
- **THEN** the page uses the Flowarr branding change's data-URI favicon instead

### Requirement: Branded Landing Page
The application SHALL serve a minimal Flowarr-branded landing page at `/` for unauthenticated visitors.

#### Scenario: Guest visits `/`
- **WHEN** an unauthenticated user visits the root URL
- **THEN** they see a simple page with the Flowarr flower logo and "Log in" / "Register" links
- **THEN** no Laravel branding or documentation links are shown

#### Scenario: Authenticated user visits `/`
- **WHEN** an authenticated user visits the root URL
- **THEN** they are redirected to the dashboard

### Requirement: Themed Rendering
All management UI pages SHALL render with the violet flower color palette applied to all shadcn components.

#### Scenario: Components use theme variables
- **WHEN** any management UI page (Dashboard, Libraries, Executions, Workers) renders
- **THEN** all shadcn components (buttons, tables, badges, cards, inputs, dialogs) use the violet theme colors
- **THEN** status badges remain distinguishable (green for completed, red for failed, etc.) but may use muted violet tones for neutral states

### Requirement: Project README
The `README.md` SHALL accurately reflect the current state of the project, listing working features and installation instructions.

#### Scenario: README accuracy
- **WHEN** reading the README
- **THEN** it SHALL NOT claim the project is non-functional
- **THEN** it SHALL list supported features: authentication, library management, queue jobs, Jellyfin webhook, settings UI
- **THEN** it SHALL link to ARCHITECTURE.md for technical details
- **THEN** it SHALL include a CI status badge

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

