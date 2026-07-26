# Frontend Component Testing

## Purpose

Provide automated component-level tests for React UI components and pages to catch rendering regressions and form state bugs during development.

## Requirements

### Requirement: Component Rendering Tests
Shared UI components SHALL render correctly with required props.

#### Scenario: Button renders with label
- **WHEN** a Button component is rendered with text content
- **THEN** the text is visible in the document
- **THEN** it has the correct default variant classes

#### Scenario: Dialog opens and closes
- **WHEN** a Dialog is rendered with trigger content
- **THEN** the trigger is visible
- **WHEN** the trigger is clicked
- **THEN** the dialog content appears

#### Scenario: Sidebar highlights active link
- **WHEN** the sidebar is rendered with a set of navigation items
- **THEN** the item matching the current route has an active/selected state

### Requirement: Form Component Tests
Form components SHALL handle user input and validation state correctly.

#### Scenario: Password input toggles visibility
- **WHEN** a PasswordInput is rendered
- **THEN** the input type is "password"
- **WHEN** the toggle button is clicked
- **THEN** the input type changes to "text"

#### Scenario: Delete user shows confirmation
- **WHEN** the DeleteUser component is rendered
- **THEN** a delete button is visible
- **WHEN** the delete button is clicked
- **THEN** a confirmation dialog appears

### Requirement: Page Smoke Tests
Pages SHALL render without crashing for both authenticated and unauthenticated states.

#### Scenario: Login page renders
- **WHEN** the login page is rendered
- **THEN** the email and password fields are present
- **THEN** a submit button is present

#### Scenario: Dashboard renders for authenticated user
- **WHEN** the dashboard is rendered with auth user data
- **THEN** the dashboard content is visible

#### Scenario: Dashboard redirects for guest
- **WHEN** the dashboard is rendered without auth user
- **THEN** a redirect or login prompt is shown

### Requirement: Passkey Component Tests
Passkey-related components SHALL handle registration and management flows.

#### Scenario: Passkey register renders
- **WHEN** PasskeyRegister is rendered
- **THEN** a register button is visible

#### Scenario: Manage passkeys lists keys
- **WHEN** ManagePasskeys is rendered with a list of passkeys
- **THEN** each passkey is listed with a delete option
