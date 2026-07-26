## ADDED Requirements

### Requirement: CI Configuration Management
The CI configuration SHALL define a PHP version matrix that matches the project's resolved dependency requirements.

#### Scenario: PHP version matrix
- **WHEN** the tests workflow runs
- **THEN** the PHP version matrix SHALL include only versions `8.4` and `8.5`
- **WHEN** PHP 8.3 is specified in the matrix
- **THEN** the workflow SHALL fail with a clear error indicating the incompatibility

### Requirement: Deterministic Dependency Installation
The CI pipeline SHALL use deterministic package install commands that fail on lockfile mismatch.

#### Scenario: npm install uses ci
- **WHEN** the tests workflow installs Node dependencies
- **THEN** it SHALL use `npm ci` instead of `npm i`
- **WHEN** the lockfile is out of sync with `package.json`
- **THEN** the install step SHALL fail with a lockfile mismatch error

#### Scenario: Composer install validates lockfile
- **WHEN** the tests workflow installs PHP dependencies
- **THEN** it SHALL use `composer install --no-interaction --prefer-dist --optimize-autoloader`
- **WHEN** the lockfile is out of sync with `composer.json`
- **THEN** the install step SHALL fail with a lockfile mismatch error

### Requirement: Build Step Optimization
The CI pipeline SHALL avoid redundant builds across workflows.

#### Scenario: Single build per workflow
- **WHEN** the tests workflow executes
- **THEN** Storybook SHALL NOT be built during test execution
- **WHEN** Storybook visual regression checking is needed
- **THEN** it SHALL be configured as a separate workflow if reintroduced

### Requirement: Job Timeout Bounds
The CI pipeline SHALL define explicit timeout limits to prevent runaway jobs.

#### Scenario: Job timeout
- **WHEN** a workflow job exceeds its configured timeout
- **THEN** the job SHALL be cancelled with a timeout error message
- **THEN** the overall workflow run SHALL be marked as failed


