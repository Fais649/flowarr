## Context

Flowarr currently uses the default shadcn/ui neutral zinc/gray palette. The app name "Flowarr" (pronounced "flower") has no visual connection in the UI. The project needs a cohesive violet/flower visual identity to stand out as a legitimate member of the *arr ecosystem.

The app uses Tailwind v4 with CSS variables for theming (via shadcn's `--primary` etc.), React 19 + Inertia, and all UI is shadcn components. The CSS lives in `resources/css/app.css` with `@theme` directives for Tailwind v4. No backend changes are needed.

## Goals / Non-Goals

**Goals:**
- Define a violet flower color palette as CSS variables in the Tailwind v4 theme
- Create an SVG violet flower logo (simple, recognizable at 32-48px)
- Apply the palette across all UI components consistently
- Add favicon using the flower logo
- Brand the auth pages with a subtle flower accent
- Keep all existing shadcn components working without structural changes
- Support both light and dark mode

**Non-Goals:**
- No backend changes (no database, API, or controller modifications)
- No new pages or features
- No changes to component structure or layout
- No redesign of the overall page architecture

## Decisions

**Tailwind v4 theme via CSS `@theme` directive (not legacy config):**
The project uses Tailwind v4 with CSS-based theming in `resources/css/app.css`. We'll add violet color tokens there rather than a `tailwind.config.ts` file. This is the v4-native approach and matches how the existing zinc palette is set up.

**CSS variable approach for shadcn compatibility:**
shadcn components use CSS variables like `--primary`, `--primary-foreground`, etc. We only need to change these variable values — no component rewrites needed. This is the least invasive approach.

**SVG logo inline in React component (not external file):**
An inline SVG React component gives us full control over sizing, theming (light/dark variants), and avoids extra HTTP requests. The flower uses overlapping petals in violet tones.

**Palette derivation from shadcn violet (not custom):**
Using Tailwind's built-in violet scale (violet-50 through violet-950) as the base, mapped to shadcn CSS variables. Custom hex values only where needed for the logo. This ensures consistency and accessibility.

**Favicon as SVG data URI:**
Using a data URI in a `<link>` tag in `app.blade.php` avoids needing a separate favicon file. If the user prefers a proper `.ico` or `.png`, we can add one later.

## Risks / Trade-offs

- [Risk] Violet might reduce contrast on some components (e.g., disabled states) → Mitigation: Use violet-600 for primary and violet-700/violet-800 for hover/active; keep danger/success/warning colors distinct
- [Risk] Users may not like the flower theme → Mitigation: The palette is the core change; the logo is a React component that can be swapped easily
- [Risk] Tailwind v4 `@theme` syntax may differ from v3 → Mitigation: Already confirmed the project uses v4 `@theme` directives in `app.css`
