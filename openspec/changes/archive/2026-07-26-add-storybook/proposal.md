## Why

Agents and developers can't visually verify frontend components during development. Vitest/Testing Library validates behavior but not rendering. TypeScript catches type errors but not visual or structural issues — leading to components that "type-check" but don't render correctly in the UI. Storybook solves this by providing an isolated component development environment with live visual feedback, making frontend work verifiable and iterative.

## What Changes

- Install and configure Storybook with React 19, Vite, Tailwind v4, and shadcn/ui
- Create initial stories for existing shared components (ui/*, nav, sidebar)
- Document the story-writing pattern for use during future development
- Optionally add visual regression testing via Chromatic or Storybook test runner

## Capabilities

### New Capabilities
- `component-catalog`: Storybook setup, configuration, addon integration, and conventions for writing stories against React 19 + shadcn/ui components

### Modified Capabilities

- `frontend-component-testing`: Add visual regression testing capability alongside existing Vitest unit tests

## Impact

- New dev dependency: `storybook` + addons (`@storybook/react-vite`, `@storybook/addon-essentials`, etc.)
- New npm scripts: `storybook` (dev server), `build-storybook` (static export)
- `resources/js/components/ui/*` — baseline stories for all shadcn components
- `resources/js/components/*` — stories for app-specific components (nav-sidebar, nav-main, etc.)
- Potentially Chromatic integration for CI visual diffing
