## ADDED Requirements

### Requirement: Build Gate
Tasks SHALL NOT be marked as complete unless all CI workflows pass green on the latest commit.

#### Scenario: Builds must pass before task completion
- **WHEN** a task is ready to be marked complete
- **THEN** the latest CI run for all 3 workflows (tests, linter, chromatic) SHALL show green
- **WHEN** any workflow is red or pending
- **THEN** the task SHALL remain incomplete until builds pass
