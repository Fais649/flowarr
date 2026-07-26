## Context

The project has 30+ React components and 9 pages with zero frontend test coverage. The existing `package.json` has no test dependencies or test scripts. CI runs backend tests (Pest) but has no frontend test step. Vite 8 is already the build tool — Vitest shares the same ecosystem and configuration.

## Goals / Non-Goals

**Goals:**
- Install and configure Vitest with @testing-library/react
- Write tests for all shared UI components (shadcn/ui primitives, shell, navigation)
- Write tests for all form/passkey components
- Write tests for all pages (auth, settings, dashboard, welcome)
- Integrate frontend tests into CI pipeline
- Document frontend testing patterns in AGENTS.md

**Non-Goals:**
- E2E or browser testing (future concern)
- Visual regression / screenshot testing
- Testing Laravel backend behavior through frontend tests
- Achieving specific coverage percentage (focus on component behavior)

## Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Test framework | Vitest | Shares Vite config, fastest DX, React 19 compatible, same ecosystem |
| DOM environment | jsdom | Lightweight, no browser needed, sufficient for component rendering tests |
| Component queries | @testing-library/react | Encourages accessible queries (getByRole, getByLabelText), resists testing implementation details |
| User interactions | @testing-library/user-event | Fires realistic events (clicks, typing) unlike fireEvent |
| Matchers | @testing-library/jest-dom | DOM-specific matchers (toBeInTheDocument, toHaveClass, toBeDisabled) |
| Test location | `tests-frontend/` mirroring `resources/js/` | Parallel to `tests/` (backend). Clear separation. Vite config resolves `@/` alias |
| Inertia mocking | vitest-inertia or manual `vi.mock('@inertiajs/react')` | Components import Inertia hooks (usePage, useForm, Link). Mock at module level |
| Route mocking | Import wayfinder-generated functions directly | Wayfinder generates typed route functions. Tests import and call them — no mocking needed |
| CI integration | Add `bun run test` step to existing `tests.yml` workflow | Runs after `bun run build`, parallel to `php artisan test` |

## Test Structure

```
tests-frontend/
├── setup.ts                    # jsdom config, matcher imports
├── components/
│   ├── ui/
│   │   ├── button.test.tsx
│   │   ├── card.test.tsx
│   │   ├── dialog.test.tsx
│   │   ├── sidebar.test.tsx
│   │   └── ...
│   ├── app-shell.test.tsx
│   ├── app-sidebar.test.tsx
│   ├── breadcrumbs.test.tsx
│   ├── heading.test.tsx
│   ├── password-input.test.tsx
│   ├── delete-user.test.tsx
│   ├── passkey-register.test.tsx
│   └── manage-passkeys.test.tsx
└── pages/
    ├── auth/
    │   ├── login.test.tsx
    │   ├── register.test.tsx
    │   ├── forgot-password.test.tsx
    │   └── reset-password.test.tsx
    ├── settings/
    │   ├── profile.test.tsx
    │   ├── security.test.tsx
    │   └── appearance.test.tsx
    ├── dashboard.test.tsx
    └── welcome.test.tsx
```

## Inertia Mocking Strategy

Components use `usePage()` for auth state and `useForm()` for form handling. Pages use `Head`, `Link`. The approach:

1. **`usePage()`** — mock to return `{ auth: { user: null | {...} }, props: {} }`
2. **`useForm()`** — mock to return a controlled form stub with `{ data, setData, patch, post, errors, processing }`
3. **`Link`** — renders as `<a>` with the href, navigation not needed in component tests
4. **`Head`** — no-op mock, just renders children

## Risks / Trade-offs

- **[Inertia mock drift]** If Inertia hooks change API between versions, the mock layer hides breakage. → Mitigation: keep mocks minimal, add smoke tests for real Inertia behavior
- **[jsdom limitations]** jsdom doesn't implement all browser APIs (layout, navigation, clipboard). → Mitigation: accept these gaps, supplement with manual testing for edge cases
- **[Component vs integration gap]** Passing tests don't guarantee the real Inertia form submission works. → Mitigation: Pest tests already cover form submission server-side. Component tests cover render + local state
- **[Test maintenance]** As components evolve, tests need updating. → Mitigation: prefer behavioral queries (getByRole) over structural ones (test IDs), minimize snapshot tests
