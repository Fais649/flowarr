## Context

The app was scaffolded from the Laravel React starter kit, which ships with a full Welcome page featuring the Laravel brand, default favicon files, sidebar footer links pointing to Laravel docs/Laracasts, and `config('app.name', 'Laravel')`. After applying the violet-flower-branding change, all of these need to be replaced or removed so the app presents a cohesive Flowarr identity.

## Goals / Non-Goals

**Goals:**
- Replace the Welcome page at `/` with a minimal Flowarr-branded landing for guests
- Redirect authenticated users from `/` to the dashboard
- Delete default favicon files (`favicon.ico`, `favicon.svg`, `apple-touch-icon.png`)
- Update sidebar footer links to point to Flowarr resources (GitHub repo placeholder)
- Remove all references to "Laravel", "Laracasts", "Laravel Starter Kit" from the UI
- Update `config/app.php` `name` if the branding change didn't already do so

**Non-Goals:**
- No new features or pages beyond the minimal landing
- No backend changes beyond route tweaks
- No CSS or component restyling

## Decisions

**Simple landing replaces Welcome page:**
The existing `welcome.tsx` is ~400 lines of Laravel-promotional SVG art and copy. Rather than try to edit it, we'll replace the entire file with a minimal page showing the Flowarr logo, app name, and login/register links. This is simpler and guarantees no stale references.

**Footer links use placeholder URLs:**
The sidebar footer currently links to the Laravel repo and docs. Since Flowarr doesn't have published docs yet, we'll link to the actual GitHub repo (`https://github.com/anomalyco/flowarr` — or whatever the real URL is) and remove the Documentation link placeholder until docs exist.

**Favicon deletion is safe:**
The branding change will set up a data-URI favicon in `app.blade.php`. The old `public/favicon.*` files are only referenced from `app.blade.php`, so deleting them and removing those `<link>` tags is sufficient.

## Risks / Trade-offs

- [Risk] Users might have bookmarked the old Welcome page → Mitigation: The route still exists at `/`; it just shows new content
- [Risk] Deleting favicon files before branding change is applied → Mitigation: These changes are applied after branding, in sequence
