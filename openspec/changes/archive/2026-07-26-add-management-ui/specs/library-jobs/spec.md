## ADDED Requirements

### Requirement: Job Toggle via UI
Users SHALL enable or disable job types for a library from the web UI.

#### Scenario: Toggle job on
- **WHEN** a user enables a job type on the library detail page
- **THEN** a LibraryJob record is created for that library and job type

#### Scenario: Toggle job off
- **WHEN** a user disables a job type on the library detail page
- **THEN** the corresponding LibraryJob record is deleted
- **THEN** existing Executions for that job are preserved
