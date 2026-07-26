## 1. Color Palette — CSS Variables

- [x] 1.1 Replace the `:root` primary/ring color values in `resources/css/app.css` with violet tones (use Tailwind violet-600 `oklch(0.546 0.245 262.881)` as `--primary`, adjust contrast for `--primary-foreground`)
- [x] 1.2 Replace the `.dark` primary/ring values with complementary deep violet
- [x] 1.3 Update sidebar CSS variables (`--sidebar-primary`, `--sidebar-ring`, etc.) to use violet tones in both light and dark
- [x] 1.4 Keep destructive/success/warning chart colors distinct from the new violet primary
- [x] 1.5 Tint all dark mode surface/border/muted colors with a subtle violet hue (Material-inspired) instead of neutral grays

## 2. Flower SVG Logo Component

- [x] 2.1 Replace `resources/js/components/app-logo-icon.tsx` with a violet flower SVG (5-petal simple flower design, rendered in violet tones)
- [x] 2.2 Update `resources/js/components/app-logo.tsx` to display "Flowarr" text next to the flower logo (instead of "Laravel Starter Kit")

## 3. App Name and Favicon

- [x] 3.1 Update `config/app.php` `name` to "Flowarr"
- [x] 3.2 Update `resources/js/components/app-logo.tsx` brand text to "Flowarr"
- [x] 3.3 Replace favicon references in `resources/views/app.blade.php` — use an SVG data URI favicon of the violet flower

## 4. Auth Page Branding

- [x] 4.1 Update `resources/js/layouts/auth/auth-simple-layout.tsx` to show a subtle violet flower accent / decorative element
- [x] 4.2 Ensure the auth page brand section displays "Flowarr" name

## 5. Verification

- [x] 5.1 Run `vendor/bin/sail bun run build` — verify no build errors
- [x] 5.2 Run `vendor/bin/sail artisan test --compact` — verify all existing tests still pass
- [x] 5.3 Visual check: login page, dashboard, sidebar, libraries, executions, workers pages render with violet theme (pending user confirmation)
