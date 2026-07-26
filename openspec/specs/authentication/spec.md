# Authentication

## Purpose

Authenticate users and manage their credentials, including passkeys and two-factor authentication.

## Requirements

### Requirement: Email + Password Login
Users SHALL authenticate using their email address and password.

#### Scenario: Successful login
- **WHEN** a user provides valid email and password
- **THEN** they are redirected to the dashboard

#### Scenario: Failed login
- **WHEN** a user provides invalid credentials
- **THEN** they see an error message and are not authenticated

### Requirement: User Registration
New users SHALL register with name, email, and password. Registration SHALL be available only when no users exist in the database (one-time onboarding).

#### Scenario: Successful registration (first user)
- **WHEN** a visitor submits valid registration details and no users exist
- **THEN** an account is created and they are logged in

#### Scenario: Registration blocked when user exists
- **WHEN** a visitor submits registration details and at least one user already exists
- **THEN** the request is rejected with a 403 response

### Requirement: Password Reset
Users SHALL reset their password via email link.

#### Scenario: Forgot password flow
- **WHEN** a user requests a password reset
- **THEN** they receive an email with a reset link
- **THEN** they can set a new password

### Requirement: Email Verification
Users SHALL verify their email address before accessing protected routes.

#### Scenario: Unverified access blocked
- **WHEN** an unverified user attempts to access dashboard or settings
- **THEN** they are prompted to verify their email

### Requirement: Passkeys (WebAuthn)
Users SHALL register and authenticate using passkeys.

#### Scenario: Register a passkey
- **WHEN** a user adds a passkey in security settings
- **THEN** the passkey is stored and available for future login

#### Scenario: Authenticate with passkey
- **WHEN** a user logs in using a passkey
- **THEN** they are authenticated without a password

### Requirement: Two-Factor Authentication (TOTP)
Users SHALL enable TOTP-based two-factor authentication.

#### Scenario: Enable 2FA
- **WHEN** a user configures 2FA in security settings
- **THEN** a TOTP secret and recovery codes are generated

#### Scenario: Login with 2FA
- **WHEN** a user with 2FA enabled logs in
- **THEN** they must provide a valid TOTP code or recovery code
