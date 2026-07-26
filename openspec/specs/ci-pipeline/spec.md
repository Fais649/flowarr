# CI Pipeline

## Purpose

Automate code quality verification and test execution to ensure every change meets project standards before merging.

## Requirements

### Requirement: Continuous Integration
The CI pipeline SHALL run on push and pull request events to the default branch.

#### Scenario: CI triggers
- **WHEN** code is pushed to the default branch
- **THEN** the CI workflow SHALL execute
- **WHEN** a pull request is opened against the default branch
- **THEN** the CI workflow SHALL execute

### Requirement: Test Execution
The CI pipeline SHALL run the full test suite using Pest.

#### Scenario: Tests pass
- **WHEN** the CI workflow runs
- **THEN** it SHALL execute `vendor/bin/sail artisan test --compact`
- **THEN** all tests SHALL pass with zero failures

### Requirement: Static Analysis
The CI pipeline SHALL run PHPStan at level 7 with zero errors.

#### Scenario: PHPStan passes
- **WHEN** the CI workflow runs
- **THEN** it SHALL execute the configured PHPStan command
- **THEN** no errors of any level SHALL be reported

### Requirement: TypeScript Type Checking
The CI pipeline SHALL run TypeScript type checking with zero errors.

#### Scenario: TypeScript passes
- **WHEN** the CI workflow runs
- **THEN** it SHALL execute `npm run types:check`
- **THEN** no type errors SHALL be reported

### Requirement: Code Style Enforcement
The CI pipeline SHALL run Laravel Pint for PHP formatting and ESLint/Prettier for JavaScript formatting.

#### Scenario: Lint passes
- **WHEN** the CI workflow runs
- **THEN** PHP formatting SHALL conform to Laravel Pint standards
- **THEN** JavaScript/TypeScript formatting SHALL conform to Prettier and ESLint rules

### Requirement: CI Status Badge
The `README.md` SHALL include a CI status badge linking to the latest workflow run.

#### Scenario: Badge displayed
- **WHEN** viewing the README on GitHub
- **THEN** a badge showing the latest CI status SHALL be visible at the top of the file
