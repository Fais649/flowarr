## ADDED Requirements

### Requirement: CI completion gate
Task completion SHALL require a green CI workflow run before the task is marked done, committed, and pushed.

#### Scenario: Task marked done
- **WHEN** all code changes for a task are implemented
- **THEN** the CI workflow SHALL be run locally
- **THEN** the results SHALL show all tests passing (green)
- **THEN** the changes SHALL be committed with a descriptive message
- **THEN** the changes SHALL be pushed to the remote repository
- **THEN** the remote CI run SHALL be verified as green

#### Scenario: CI fails after push
- **WHEN** the remote CI workflow reports a failure
- **THEN** the task SHALL NOT be marked complete
- **THEN** the failure SHALL be fixed before proceeding
