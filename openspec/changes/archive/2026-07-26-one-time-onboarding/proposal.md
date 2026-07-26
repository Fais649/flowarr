## Why

Flowarr is designed as a single-user application for private media server administrators, but currently anyone who reaches the app can register an account. This creates unnecessary complexity (multi-user concerns) and a security risk (unintended signups). On first launch, the admin should be guided through setup — name, email, password, and a passkey — after which registration locks permanently.

## What Changes

- Add a first-run detection that checks if any users exist in the database
- Replace the public `/register` page with a one-time onboarding wizard that only appears when no users exist
- Redirect all other visits to the login page when no users exist (with a notice that setup is required)
- After the initial admin user is created, disable registration permanently — the `/register` route redirects to login or returns 404
- Provide an Artisan command to re-enable registration for recovery scenarios (e.g., admin lockout)
- Update the `CreateNewUser` Fortify action to refuse new users when one already exists
- Frontend: build a stepper/wizard onboarding page replacing the standard register page

## Capabilities

### New Capabilities
- `one-time-onboarding`: first-launch detection, guided admin setup wizard (name, email, password, passkey), permanent registration lock after completion

### Modified Capabilities
- `authentication`: the User Registration requirement changes from "anyone can register" to "registration is available only when no users exist (one-time)"

## Impact

- Authentication spec (`openspec/specs/authentication/spec.md`) — Requirement: User Registration will be updated with a one-time constraint
- Fortify `CreateNewUser` action — gated behind a check that no users exist
- `routes/web.php` — `/register` route behavior changes conditionally
- `resources/js/Pages/Auth/Register.tsx` — replaced with a multi-step Onboarding page
- New `app/Http/Controllers/Auth/OnboardingController.php` — handles first-run state
- New Artisan command for admin recovery
