## 1. Landing Page

- [x] 1.1 Replace `resources/js/pages/welcome.tsx` with a minimal Flowarr-branded page (flower logo, "Flowarr" name, Log in/Register links for guests, no Laravel references)
- [x] 1.2 Ensure authenticated users visiting `/` are redirected to the dashboard (update route logic or page component)

## 2. Favicon Files

- [x] 2.1 Delete `public/favicon.ico`
- [x] 2.2 Delete `public/favicon.svg`
- [x] 2.3 Delete `public/apple-touch-icon.png`
- [x] 2.4 Remove the favicon `<link>` tags from `resources/views/app.blade.php` (the branding change will add the data-URI favicon)

## 3. Sidebar Footer Links

- [x] 3.1 Update `resources/js/components/app-sidebar.tsx` footer nav items — replace Laravel repo URL with Flowarr's GitHub repo URL, remove or replace Laravel Documentation link

## 4. Brand Name Cleanup

- [x] 4.1 If not already done in branding change, update `config/app.php` `name` to "Flowarr"
- [x] 4.2 Verify no "Laravel", "Laracasts", or "Laravel Starter Kit" text remains in any UI component

## 5. Verification

- [x] 5.1 Run `vendor/bin/sail bun run build` — verify no build errors
- [x] 5.2 Run `vendor/bin/sail artisan test --compact` — verify all existing tests still pass
