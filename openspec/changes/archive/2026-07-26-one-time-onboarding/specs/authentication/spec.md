## MODIFIED Requirements

### Requirement: User Registration
New users SHALL register with name, email, and password. Registration SHALL be available only when no users exist in the database (one-time onboarding).

#### Scenario: Successful registration (first user)
- **WHEN** a visitor submits valid registration details and no users exist
- **THEN** an account is created and they are logged in

#### Scenario: Registration blocked when user exists
- **WHEN** a visitor submits registration details and at least one user already exists
- **THEN** the request is rejected with a 403 response
