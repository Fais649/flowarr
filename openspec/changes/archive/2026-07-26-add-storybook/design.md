## Context

The project uses React 19, Tailwind v4, shadcn/ui components, and Inertia v3. Frontend code lives in `resources/js/`. Existing testing uses Vitest + Testing Library for unit/component tests but provides no visual rendering feedback. Agents currently cannot verify UI output and rely on type-checking alone.

Storybook v8 provides an isolated React component development environment that integrates with Vite natively. It renders components with the app's actual theme (dark/light mode, violet palette) and Tailwind classes, giving immediate visual feedback.

## Goals / Non-Goals

**Goals:**
- Storybook dev server that auto-discovers stories in `resources/js/`
- Stories covering all `resources/js/components/ui/*` shadcn components
- Stories for key app-specific components (sidebar, nav, layouts)
- Documentation of the story-writing convention for agents
- Visual regression safety net via Chromatic or Storybook test runner

**Non-Goals:**
- Full page-level visual tests (handled by Playwright)
- Replacing Vitest unit tests (they serve different purposes)
- Stories for every possible component variant or state
- Production storybook deployment

## Decisions

1. **Storybook with React+Vite builder** — Storybook v8's `@storybook/react-vite` builder integrates directly with the existing Vite config, inheriting the Tailwind v4 PostCSS setup and path aliases (`@/`). No manual webpack configuration needed.

2. **CSF 3 format** — Use Component Story Format 3 (`.stories.tsx` co-located with components) for consistency with modern Storybook practices. This keeps stories close to their components and makes them discoverable by agents.

3. **Addons** — Install `@storybook/addon-essentials` (controls, actions, docs, viewport) plus `@storybook/addon-themes` for dark/light mode toggling. These provide the agent with interactive controls to verify component states.

4. **Chromatic for CI** — Optional GitHub Action integration with Chromatic for automatic visual diffing on PRs. Agents can see before/after screenshots of visual changes.

5. **Co-located stories** — Place `.stories.tsx` files next to their components (`ui/button.stories.tsx`, `nav-main.stories.tsx`). Configure the glob pattern `resources/js/**/*.stories.tsx` in `main.ts`. This matches the existing Vitest test convention and keeps things simple.

## Risks / Trade-offs

- **[Maintenance] Story files add surface area** → Limit initial stories to shared/ui components and a few critical app components. Don't force stories for one-off page components.
- **[CI Time] Chromatic builds add minutes to CI** → Make Chromatic optional (manual trigger or separate workflow). Skip for small PRs.
- **[False Confidence] Stories don't catch real rendering** → Stories verify isolated components, not full-page interactions. Pair with Playwright for E2E coverage.
