# Settings

## Purpose

Allow authenticated users to manage their profile, security, and appearance preferences.

## Requirements

### Requirement: Profile Management
Users SHALL view and update their profile information.

#### Scenario: Edit profile
- **WHEN** a user visits /settings/profile
- **THEN** they see their current name and email
- **WHEN** they submit changes
- **THEN** the profile is updated

#### Scenario: Delete account
- **WHEN** a verified user deletes their account
- **THEN** the account and all associated data are removed

### Requirement: Security Settings
Users SHALL manage their password and authentication methods.

#### Scenario: Change password
- **WHEN** a user submits a new password with current password confirmation
- **THEN** the password is updated

#### Scenario: Manage passkeys
- **WHEN** a user visits security settings
- **THEN** they can register, view, and delete passkeys

### Requirement: Appearance Settings
Users SHALL choose between light, dark, and system theme.

#### Scenario: Switch theme
- **WHEN** a user selects a theme in appearance settings
- **THEN** the UI updates immediately and the preference is persisted

### Requirement: Worker Settings
The system SHALL provide a settings page for configuring per-job-type concurrency limits.

#### Scenario: Navigate to worker settings
- **WHEN** a user navigates to /settings/workers
- **THEN** a page is displayed with settings for each job type
- **THEN** each setting has a label, description, and number input
- **WHEN** the user updates a value and submits
- **THEN** the new limit is persisted and reflected immediately
