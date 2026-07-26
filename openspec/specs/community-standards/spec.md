# Community Standards

## Purpose

Provide clear guidelines and governance for contributors to ensure a welcoming, well-documented open-source project.

## Requirements

### Requirement: Contributing Guide
The project SHALL include a `CONTRIBUTING.md` that explains how to set up the development environment, run tests, follow code style, and submit changes.

#### Scenario: Contributing guide present
- **WHEN** viewing the project root
- **THEN** a `CONTRIBUTING.md` file SHALL exist
- **THEN** it SHALL include setup instructions, test commands, and PR workflow

### Requirement: Code of Conduct
The project SHALL include a `CODE_OF_CONDUCT.md` adopting the Contributor Covenant v2.1.

#### Scenario: Code of conduct present
- **WHEN** viewing the project root
- **THEN** a `CODE_OF_CONDUCT.md` file SHALL exist
- **THEN** it SHALL reference a contact method for reporting violations

### Requirement: Security Policy
The project SHALL include a `SECURITY.md` describing how to report vulnerabilities.

#### Scenario: Security policy present
- **WHEN** viewing the project root
- **THEN** a `SECURITY.md` file SHALL exist
- **THEN** it SHALL explain the disclosure process and expected timeline

### Requirement: Issue Templates
The `.github/ISSUE_TEMPLATE/` directory SHALL contain templates for bug reports and feature requests.

#### Scenario: Bug report template
- **WHEN** creating a new issue
- **THEN** a bug report template SHALL be available
- **THEN** it SHALL include fields for: description, steps to reproduce, expected behavior, actual behavior, environment

#### Scenario: Feature request template
- **WHEN** creating a new feature request
- **THEN** a feature request template SHALL be available
- **THEN** it SHALL include fields for: problem statement, proposed solution, alternatives considered

### Requirement: Pull Request Template
The `.github/` directory SHALL contain a `PULL_REQUEST_TEMPLATE.md` describing how to submit PRs.

#### Scenario: PR template present
- **WHEN** creating a new pull request
- **THEN** the PR template SHALL be loaded
- **THEN** it SHALL include checklist items for: tests passing, linting, documentation updated, changelog entry
