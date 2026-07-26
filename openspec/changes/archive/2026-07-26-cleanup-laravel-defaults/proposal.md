## Why

The app still ships with Laravel starter kit defaults — a Laravel-branded Welcome page, Laravel favicons, a "Laravel Starter Kit" brand name in the sidebar, and footer links pointing to Laravel docs/ Laracasts. After the violet-flower-branding change gives Flowarr its identity, these remnants need to be removed so nothing contradicts or dilutes the new brand.

## What Changes

- Replace the Welcome page (`resources/js/pages/welcome.tsx`) with a Flowarr-branded landing page or a redirect to the Dashboard (unauthenticated → login, authenticated → dashboard)
- Remove the `public/favicon.ico`, `public/favicon.svg`, `public/apple-touch-icon.png` (favicon will be served via the branding change's data-URI approach)
- Update footer nav links in the sidebar to point to Flowarr's GitHub repo and docs instead of Laravel links
- Ensure no "Laravel", "Laracasts", or starter-kit references remain anywhere in the UI

## Capabilities

### New Capabilities
- (none — this is purely cleanup)

### Modified Capabilities
- `management-ui`: Landing/welcome page, sidebar footer links, removed stale assets

## Impact

- `resources/js/pages/welcome.tsx` — replaced with branded content or redirected
- `routes/web.php` — potentially update the `/` route behavior
- `resources/js/components/app-sidebar.tsx` — footer nav items (repo + docs links)
- `public/favicon.ico`, `public/favicon.svg`, `public/apple-touch-icon.png` — deleted
- No backend/model/database changes
