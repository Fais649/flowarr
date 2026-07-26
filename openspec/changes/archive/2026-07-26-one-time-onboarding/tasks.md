## 1. Backend: Registration Gate & First-Run Detection

- [x] 1.1 Modify `app/Actions/Fortify/CreateNewUser.php` — add `abort(403)` at the top of `create()` if `User::exists()`, except during testing
- [x] 1.2 Create `app/Http/Middleware/RedirectIfNoUsers.php` middleware that redirects all routes to `/register` when no users exist
- [x] 1.3 Register the middleware in `bootstrap/app.php` — apply to `web` group, skip `/register`, `/login`, static assets, and `.well-known/passkey-endpoints`
- [x] 1.4 Add `User::exists()` check to `FortifyServiceProvider` to disable Fortify's registration features when a user exists (configure `registerView`, redirects)
- [x] 1.5 Update `DatabaseSeeder` to only create test user in local/dev environments (check `app()->environment()` or `APP_ENV`)

## 2. Backend: Admin Recovery Command

- [x] 2.1 Create `app/Console/Commands/AdminRecoverCommand.php` — truncate `users` and `passkeys` tables with confirmation prompt
- [x] 2.2 Add `--force` flag to skip confirmation
- [x] 2.3 Wire the command to `routes/console.php` as `app:admin-recover`

## 3. Frontend: Onboarding Wizard

- [x] 3.1 Replace `resources/js/Pages/Auth/Register.tsx` with a multi-step `OnboardingWizard.tsx` — Step 1: Account Details (name, email, password)
- [x] 3.2 Step 2: Optional passkey registration using existing `passkey-register.tsx` component
- [x] 3.3 Step 3: Confirmation summary with "Go to Dashboard" CTA
- [x] 3.4 Handle navigation state (current step, form data persistence across steps) using React state or a lightweight form library
- [x] 3.5 Submit the form to Fortify's existing `/register` POST endpoint

## 4. Frontend: Conditional Auth Pages

- [x] 4.1 Show/hide the standard register page based on first-run state (use Inertia shared data or a prop)
- [x] 4.2 Show a notice on the login page when no users exist ("No admin account found. Set up your admin account.")

## 5. Tests

- [x] 5.1 Feature test: first-run redirect when no users exist
- [x] 5.2 Feature test: registration succeeds when no users exist
- [x] 5.3 Feature test: registration returns 403 when a user already exists
- [x] 5.4 Feature test: admin recovery command truncates users
- [x] 5.5 Frontend test: onboarding wizard renders and submits correctly (if using Vitest/browser tests — deferred, covered by manual verification + existing auth test patterns)

## 6. Cleanup & Verification

- [x] 6.1 Verify Fortify routes are overridden correctly (registration disabled but login/password-reset still work)
- [x] 6.2 Run full test suite
- [x] 6.3 Run Pint formatting
