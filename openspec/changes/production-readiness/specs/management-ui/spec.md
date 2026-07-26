## MODIFIED Requirements

### Requirement: Project README
The `README.md` SHALL accurately reflect the current state of the project, listing working features and installation instructions.

#### Scenario: README accuracy
- **WHEN** reading the README
- **THEN** it SHALL NOT claim the project is non-functional
- **THEN** it SHALL list supported features: authentication, library management, queue jobs, Jellyfin webhook, settings UI
- **THEN** it SHALL link to ARCHITECTURE.md for technical details
- **THEN** it SHALL include a CI status badge
