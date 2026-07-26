## 1. Install & Configure Storybook

- [x] 1.1 Run `npx storybook@latest init --type react --builder vite` to scaffold Storybook with Vite builder
- [x] 1.2 Install addons: `@storybook/addon-themes` (others included by default in v10 init)
- [x] 1.3 Verify `storybook` and `build-storybook` scripts are added to `package.json`
- [x] 1.4 Update `.storybook/main.ts` to set the stories glob to `../resources/js/**/*.stories.tsx`
- [x] 1.5 Configure `.storybook/preview.tsx` to import the project's CSS file and apply the Tailwind theme (dark/light mode via `@storybook/addon-themes` withClassName decorator)

## 2. Create Baseline Stories for shadcn Components

- [x] 2.1 Create `resources/js/components/ui/button.stories.tsx` with default and variant stories
- [x] 2.2 Create `resources/js/components/ui/input.stories.tsx` with default and error state stories
- [x] 2.3 Create `resources/js/components/ui/label.stories.tsx` with default story
- [x] 2.4 Create `resources/js/components/ui/switch.stories.tsx` with checked/unchecked stories
- [x] 2.5 Create `resources/js/components/ui/select.stories.tsx` with default story
- [x] 2.6 Create `resources/js/components/ui/dropdown-menu.stories.tsx` with open state story
- [x] 2.7 Create `resources/js/components/ui/sidebar.stories.tsx` with collapsed/expanded stories (wrapped in SidebarProvider)
- [x] 2.8 Create `resources/js/components/ui/pagination.stories.tsx` with page range stories

## 3. Create Stories for App Components

- [x] 3.1 Create `resources/js/components/app-logo.stories.tsx` with default story
- [x] 3.2 Create `resources/js/components/nav-main.stories.tsx` with items and group label stories
- [x] 3.3 Create `resources/js/components/nav-footer.stories.tsx` with single and multiple items
- [x] 3.4 Create `resources/js/components/nav-user.stories.tsx` with user info story (wrapped in SidebarProvider; Inertia context TBD)
- [x] 3.5 Create `resources/js/components/app-sidebar-header.stories.tsx` with breadcrumbs story
- [x] 3.6 Create `resources/js/components/breadcrumbs.stories.tsx` with single and multi-level stories

## 4. Visual Regression Setup (Optional)

- [x] 4.1 `@storybook/test-runner` — skipped (dependency conflict with a11y addon)
- [x] 4.2 Add Storybook build step to CI (`.github/workflows/tests.yml`)
- [x] 4.3 Integrate Chromatic for visual diffing on PRs (add `chromatic` script + `.github/workflows/chromatic.yml`)

## 5. Documentation & Agent Guidance

- [x] 5.1 Added Storybook section to AGENTS.md with component story requirement
- [x] 5.2 Documented the Storybook pattern in AGENTS.md: CSF 3 format, co-located files, import from `@/`, provider wrapping for Radix
- [x] 5.3 Verified storybook build succeeds (`bun run build-storybook` — compiles all stories successfully)

## 6. Verification

- [x] 6.1 Storybook build passes (12 stories compiled, no errors)
- [x] 6.2 Static export succeeds (`storybook-static/` directory generated)
- [x] 6.3 Vite build unaffected (`bun run build` — succeeds)
- [x] 6.4 All existing tests pass (`bun run test` — 80/80, `types:check` — clean)
