## ADDED Requirements

### Requirement: Clean Brand State
No Laravel starter-kit branding, logos, or references SHALL appear in the rendered UI.

#### Scenario: No Laravel references
- **WHEN** any page renders
- **THEN** no "Laravel", "Laracasts", "Laravel Starter Kit", or similar text appears in the UI

#### Scenario: Favicon files removed
- **WHEN** the application loads
- **THEN** `public/favicon.ico`, `public/favicon.svg`, and `public/apple-touch-icon.png` no longer exist
- **THEN** the page uses the Flowarr branding change's data-URI favicon instead

### Requirement: Branded Landing Page
The application SHALL serve a minimal Flowarr-branded landing page at `/` for unauthenticated visitors.

#### Scenario: Guest visits `/`
- **WHEN** an unauthenticated user visits the root URL
- **THEN** they see a simple page with the Flowarr flower logo and "Log in" / "Register" links
- **THEN** no Laravel branding or documentation links are shown

#### Scenario: Authenticated user visits `/`
- **WHEN** an authenticated user visits the root URL
- **THEN** they are redirected to the dashboard
