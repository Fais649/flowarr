## Why

Flowarr (pronounced "flower") integrates with the *arr media stack but its UI looks completely generic — no identity, no visual connection to its name. A violet-flower themed brand gives the project a distinctive, memorable identity that makes it feel like a first-class member of the *arr ecosystem rather than a faceless tool.

## What Changes

- Add a custom violet/purple color palette as the app's primary theme
- Create an SVG logo incorporating a violet flower motif
- Update Tailwind theme configuration (CSS variables, dark mode) to use the new palette
- Redesign the sidebar nav icon to use the new logo
- Update the app name display and page `<title>` to use "Flowarr"
- Add a branded favicon
- Add a "powered by" flower accent in the login/register pages
- Ensure all existing shadcn components render correctly with the new palette

## Capabilities

### New Capabilities
- `branding-theme`: Custom violet flower color palette, logo, favicon, and visual identity applied across all UI pages

### Modified Capabilities
- `management-ui`: Update color references to use the new branding theme variables instead of default shadcn neutrals

## Impact

- `tailwind.config.ts` or CSS variable overrides — new violet primary palette
- New SVG logo and favicon assets in `resources/js/components/` or `public/`
- `app.blade.php` — updated title and favicon
- `app-sidebar.tsx` — updated logo/brand display
- `resources/css/` — possible CSS variable overrides for the theme
- Login/register pages — optional branded accent
- No backend changes
