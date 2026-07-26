# One-Time Onboarding

## Purpose

Guide the first and only admin through initial setup and permanently lock registration after completion.

## Requirements

### Requirement: First-run detection
The system SHALL detect when no users exist in the database and present an onboarding wizard instead of the login page.

#### Scenario: No users exist on first visit
- **WHEN** a visitor reaches any page and no users exist in the database
- **THEN** they are redirected to the onboarding wizard at `/register`

#### Scenario: Users exist on first visit
- **WHEN** a visitor reaches any page and at least one user exists
- **THEN** they see the standard login page

### Requirement: Onboarding wizard
The system SHALL provide a multi-step onboarding wizard that guides the admin through initial setup.

#### Scenario: Complete onboarding flow
- **WHEN** a visitor completes the onboarding wizard (name, email, password, optional passkey)
- **THEN** an admin account is created
- **THEN** they are authenticated and redirected to the dashboard

### Requirement: Registration lock after onboarding
The system SHALL permanently disable user registration after the first admin account is created.

#### Scenario: Registration blocked after admin exists
- **WHEN** a visitor attempts to access `/register` and a user already exists
- **THEN** they are redirected to the login page

#### Scenario: Direct API registration blocked
- **WHEN** a POST request is made to the registration endpoint and a user already exists
- **THEN** the request is rejected with a 403 response

### Requirement: Admin recovery command
The system SHALL provide an Artisan command to reset the admin account for recovery scenarios.

#### Scenario: Run recovery command with force
- **WHEN** an admin runs `php artisan app:admin-recover --force`
- **THEN** all users and passkeys are deleted
- **THEN** the system returns to first-run state
