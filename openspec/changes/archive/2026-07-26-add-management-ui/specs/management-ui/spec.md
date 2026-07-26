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
