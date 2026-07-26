## ADDED Requirements

### Requirement: Visual Regression Tests
Visual regression testing SHALL be available to catch unintended visual changes in components.

#### Scenario: Snapshot comparison
- **WHEN** a story's component is rendered
- **THEN** a visual snapshot is captured for comparison
- **WHEN** the component's visual output changes on subsequent runs
- **THEN** the test fails with a diff image showing the change

#### Scenario: CI integration
- **WHEN** a PR is opened with component changes
- **THEN** visual snapshots are compared against the base branch
- **THEN** differences are reported as CI check annotations
