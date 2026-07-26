## Context

Flowarr uses Laravel Fortify for authentication, including registration, login, password reset, email verification, and passkeys. Currently, the standard `/register` route is public — anyone can create an account. The app is single-user by design but has no enforcement mechanism. The existing `DatabaseSeeder` creates a test user, which would prevent first-run detection if seeded.

The architecture uses Inertia v3 + React 19 for the frontend, with a routes file that maps `/register` to an auth register page.

## Goals / Non-Goals

**Goals:**
- Detect first-run state (no users in DB) and redirect to a setup wizard
- Build a multi-step onboarding wizard that collects name, email, password, and optionally a passkey
- After onboarding completes, permanently disable the `/register` path
- Provide a recovery mechanism via Artisan command for admin lockout scenarios
- Keep existing auth flows (login, password reset, email verification, 2FA) unchanged

**Non-Goals:**
- Multi-user support of any kind
- Invitation or approval-based registration
- Soft-deleting or toggling registration on/off from UI
- Role or permission systems

## Decisions

1. **Fortify `CreateNewUser` gate** — Add a check `if (User::exists()) abort(403)` at the top of `CreateNewUser::create()`. This is the single enforcement point. Even if a rogue route or controller tries to create a user, Fortify's action is the bottleneck. This is simpler and more secure than middleware-based approaches.

2. **Middleware/redirect for `/register`** — Keep the Fortify route registered but modify the response behavior. Use a small middleware or a conditional in `FortifyServiceProvider` to redirect away from `/register` when users exist, and redirect to `/register` (which renders the onboarding wizard) when no users exist. Alternatively, use Laravel's `Fortify::createUsersUsing()` to swap in a custom action that respects the one-time constraint, and handle the redirect via Inertia shared data.

3. **Onboarding wizard as replacement for register page** — Replace the existing `Register.tsx` page with a multi-step wizard (`OnboardingWizard.tsx`) at the same route (`/register`). Components: Step 1 (Account Details — name, email, password), Step 2 (Passkey Registration — optional, uses existing passkey components), Step 3 (Confirmation — summary + "Let's go" CTA). This avoids creating a new route and leverages the existing Fortify registration endpoint.

4. **Recovery command** — `php artisan admin:recover` — truncates users/passkeys tables, prints a console warning, then re-enables first-run mode. Use `--force` flag to skip confirmation prompt. This is intentionally destructive (not a toggle) because the single-admin model means you're starting fresh.

5. **Seeder adjustment** — The `DatabaseSeeder` currently creates a test user, which would prevent first-run detection in dev. Use an env flag or `!app()->isProduction()` check to only seed the user in local/dev environments, or skip seeding entirely and let the admin onboard naturally.

## Risks / Trade-offs

- **[Risk] Admin lockout if passkey fails or email is unreachable** → Mitigation: the recovery command exists. Also, the onboarding should allow skipping passkey setup (can be added later in settings).
- **[Risk] Race condition on first launch** (two browser tabs hit `/register` simultaneously, both see "no users exist" and try to register) → Mitigation: use a database transaction with `SELECT ... FOR UPDATE` or a unique constraint, plus application-level check. The `CreateNewUser` gate + DB uniqueness on email is sufficient — the second attempt will fail at email uniqueness.
- **[Trade-off] Artisan command is destructive** — It truncates data rather than toggling a flag. This is intentional: the app is single-user, so "re-enable registration" effectively means "reset your admin account." A flag approach would add a persisted setting that complicates the first-run check.
