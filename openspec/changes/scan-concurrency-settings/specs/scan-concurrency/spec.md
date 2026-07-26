# Scan Concurrency

## Purpose

Configure how many libraries scan in parallel during scheduled scan runs.

## Requirements

### Requirement: Scan Concurrency Setting
The system SHALL expose a configurable `scan.concurrency` setting.

#### Scenario: Default value
- **WHEN** no scan concurrency has been configured
- **THEN** the system defaults to 2

#### Scenario: Store concurrency setting
- **WHEN** a user sets scan concurrency via the settings page
- **THEN** the value is persisted in the settings table
- **THEN** it takes effect on the next scan run

### Requirement: Scan Settings UI
Authenticated users SHALL view and update scan concurrency from a settings page.

#### Scenario: View scan settings
- **WHEN** a user visits `/settings/scan`
- **THEN** they see the current concurrency limit in an editable number input

#### Scenario: Update concurrency
- **WHEN** a user changes the value and submits
- **THEN** validation ensures the value is at least 1
- **THEN** the setting is persisted
- **THEN** they see a success message

### Requirement: Scan Concurrency Enforced
The system SHALL respect the concurrency limit when scanning libraries.

#### Scenario: Concurrent library scanning
- **WHEN** `scan:libraries` finds multiple due libraries
- **THEN** up to `scan.concurrency` libraries scan simultaneously
- **THEN** the remainder are picked up on the next schedule tick
