## 1. Setup

- [x] 1.1 Install dev dependencies: `vitest`, `@testing-library/react`, `@testing-library/jest-dom`, `@testing-library/user-event`, `jsdom`
- [x] 1.2 Create `vitest.config.ts` sharing Vite config, using jsdom environment
- [x] 1.3 Create `tests-frontend/setup.ts` importing jest-dom matchers
- [x] 1.4 Create `tests-frontend/helpers.tsx` with Inertia mock utilities (usePage, useForm, Link, Head)
- [x] 1.5 Add `test`, `test:watch`, `test:coverage` scripts to `package.json`
- [x] 1.6 Verify `bun run test` runs and reports 0 tests (passing setup)

## 2. Shared UI Component Tests

- [x] 2.1 Write tests for `ui/button.tsx` (variants, sizes, disabled state, children rendering)
- [x] 2.2 Write tests for `ui/card.tsx` (title, description, content slots)
- [x] 2.3 Write tests for `ui/dialog.tsx` (open/close, overlay, content visibility)
- [x] 2.4 Write tests for `ui/input.tsx` (label, placeholder, value, disabled, error state)
- [x] 2.5 Write tests for `ui/badge.tsx` (variants, children)
- [x] 2.6 Write tests for `ui/avatar.tsx` (initials fallback, image src)
- [x] 2.7 Write tests for `ui/skeleton.tsx` (renders with class)
- [x] 2.8 Write tests for `ui/spinner.tsx` (renders, custom size)
- [x] 2.9 Write tests for `ui/tooltip.tsx` (trigger, content on hover)

## 3. Shell and Navigation Component Tests

- [x] 3.1 Write tests for `app-shell.tsx` (renders header, sidebar, content slot)
- [x] 3.2 Write tests for `app-sidebar.tsx` (nav items render, active state)
- [x] 3.3 Write tests for `app-sidebar-header.tsx` (logo, branding)
- [x] 3.4 Write tests for `breadcrumbs.tsx` (items render, active item not linked)
- [x] 3.5 Write tests for `heading.tsx` (title, description, actions slot)
- [x] 3.6 Write tests for `app-header.tsx` (user menu trigger, breadcrumbs)
- [x] 3.7 Write tests for `nav-main.tsx` and `nav-user.tsx` (links, user info)
- [x] 3.8 Write tests for `nav-footer.tsx` (footer links)
- [x] 3.9 Write tests for `text-link.tsx` (renders as styled anchor)

## 4. Form and Passkey Component Tests

- [x] 4.1 Write tests for `password-input.tsx` (visibility toggle, disabled, error message)
- [x] 4.2 Write tests for `input-error.tsx` (message rendering, hidden when no message)
- [x] 4.3 Write tests for `alert-error.tsx` (message, dismiss behavior)
- [x] 4.4 Write tests for `delete-user.tsx` (confirm dialog, password input, cancel)
- [x] 4.5 Write tests for `passkey-register.tsx` (register button, loading state)
- [x] 4.6 Write tests for `passkey-verify.tsx` (verify prompt)
- [x] 4.7 Write tests for `passkey-item.tsx` (key name, delete button)
- [x] 4.8 Write tests for `manage-passkeys.tsx` (list, empty state, register)

## 5. Page Tests

- [x] 5.1 Write tests for `auth/login.tsx` (form fields, submit renders errors, loading state)
- [x] 5.2 Write tests for `auth/register.tsx` (form fields, name/email/password/confirm)
- [x] 5.3 Write tests for `auth/forgot-password.tsx` (email field, success message)
- [x] 5.4 Write tests for `auth/reset-password.tsx` (token hidden field, new password fields)
- [x] 5.5 Write tests for `settings/profile.tsx` (name/email fields, save button)
- [x] 5.6 Write tests for `settings/security.tsx` (password form, passkey section)
- [x] 5.7 Write tests for `settings/appearance.tsx` (theme options, active state)
- [x] 5.8 Write tests for `dashboard.tsx` (placeholder renders, grid layout)
- [x] 5.9 Write tests for `welcome.tsx` (guest nav links, authenticated redirect)

## 6. CI and Documentation

- [x] 6.1 Add `bun run test` step to `.github/workflows/tests.yml`
- [x] 6.2 Update `composer.json` `ci:check` script to include frontend tests
- [x] 6.3 Add frontend testing patterns to `AGENTS.md` (how to write tests, mock Inertia, run test suite)
