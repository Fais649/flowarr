# Debug Tools

## Purpose

Provide debug-only UI buttons for development and testing workflows.

## Requirements

### Requirement: Debug-Restore Button
The system SHALL show a button in the UI (dev mode only) to trigger the test data restore script.

#### Scenario: Button visible in dev mode
- **WHEN** `APP_DEBUG=true` or `APP_ENV=local`
- **THEN** a "Restore Test Data" button SHALL appear on the configuration page
- **THEN** clicking it SHALL execute `restore-test-data.sh` on the server
- **THEN** the button SHALL be hidden in production

#### Scenario: Restore executed
- **WHEN** the button is clicked
- **THEN** `restore-test-data.sh` runs in the background
- **THEN** a success toast SHALL be shown
