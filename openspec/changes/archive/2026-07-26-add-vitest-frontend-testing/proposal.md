## Why

The project has no frontend test coverage — 30+ React components, 9 pages, and zero automated verification that they render correctly. Backend changes are validated by Pest tests, but frontend regressions (a form field missing, a button not rendering, a sidebar link not highlighting) go unnoticed. Adding Vitest closes this gap with fast, low-overhead component tests.

## What Changes

- Add `vitest`, `@testing-library/react`, `@testing-library/jest-dom`, `@testing-library/user-event`, and `jsdom` as dev dependencies
- Add `vitest.config.ts` sharing Vite's existing configuration
- Add `test` and `test:coverage` scripts to `package.json`
- Add `tests-frontend/` directory parallel to `tests/` with mirrors of the page/component structure
- Write initial test suite covering: shared UI components (button, card, dialog, etc.), form components (password-input, delete-user), navigation (sidebar, breadcrumbs, header), and critical pages (dashboard, login, register)
- Add `test` step to `composer.json`'s `ci:check` script (or near it)
- Add test patterns to AGENTS.md so agents know what and how to test

## Capabilities

### New Capabilities

- `frontend-component-testing`: Vitest + Testing Library suite for React components and pages

### Modified Capabilities

None — no existing spec's requirements change. Testing is a new concern orthogonal to existing capabilities.

## Impact

- 5 new dev dependencies
- `vitest.config.ts` in project root
- `tests-frontend/` directory with mirror of `resources/js/` structure
- `package.json`: new `test` and `test:coverage` scripts
- `composer.json`: `ci:check` script updated to include frontend tests
- `AGENTS.md`: new validation patterns section for frontend testing
- `.github/workflows/tests.yml`: CI adds frontend test step
