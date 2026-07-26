## ADDED Requirements

### Requirement: Accurate project overview
The README SHALL present an accurate summary of the project's current state, including that it is functional and all core features are implemented.

#### Scenario: README reflects functional state
- **WHEN** a user reads the README introduction
- **THEN** it SHALL describe the project as functional rather than non-functional or scaffolding

#### Scenario: Roadmap completeness
- **WHEN** a user reads the features/roadmap section
- **THEN** all implemented features SHALL be marked as complete

### Requirement: Tech stack documentation
The README SHALL document the project's technology stack.

#### Scenario: Stack listed
- **WHEN** a user reads the README
- **THEN** they SHALL find a section listing the key technologies: Laravel, React/Inertia, PostgreSQL, RabbitMQ, Redis, Tailwind CSS

### Requirement: Development setup instructions
The README SHALL include step-by-step setup instructions for local development.

#### Scenario: Setup steps present
- **WHEN** a developer reads the README
- **THEN** they SHALL find instructions for cloning, installing dependencies, configuring environment, and starting the development server

### Requirement: Testing documentation
The README SHALL document how to run both backend (Pest) and frontend (Vitest) tests.

#### Scenario: Test commands documented
- **WHEN** a developer reads the README
- **THEN** they SHALL find commands for running PHP tests and frontend tests separately

### Requirement: Contribution guidelines
The README SHALL include contribution guidelines that reference the OpenSpec change workflow, testing requirements, and code style tools (Pint, ESLint, Prettier).

#### Scenario: Contribution section present
- **WHEN** a contributor reads the README
- **THEN** they SHALL find guidelines for proposing changes, writing tests, and running linters

### Requirement: Jellyfin integration documentation
The README SHALL document the Jellyfin webhook setup for pause/resume functionality.

#### Scenario: Webhook setup documented
- **WHEN** a user reads the README
- **THEN** they SHALL find instructions for configuring the Jellyfin Webhook plugin to communicate with Flowarr
