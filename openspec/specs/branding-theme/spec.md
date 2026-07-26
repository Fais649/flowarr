# Branding Theme

## Purpose

Define the violet flower visual identity for the Flowarr application, including color palette, logo, and brand name display.

## Requirements

### Requirement: Violet Flower Color Palette
The application SHALL use a violet/purple color palette as its primary theme, inspired by violet flowers, reinforcing the "Flowarr" (pronounced "flower") brand identity.

#### Scenario: Primary colors applied globally
- **WHEN** any authenticated page renders
- **THEN** the primary color is a violet shade (e.g., violet-600 #7C3AED or similar)
- **THEN** all interactive elements (buttons, links, active states) use the violet primary
- **THEN** the dark mode variant uses a complementary deep violet

#### Scenario: Sidebar shows violet accent
- **WHEN** the sidebar renders
- **THEN** the active navigation item has a violet accent/indicator
- **THEN** the app logo/brand area uses the violet palette

### Requirement: Custom Logo
The application SHALL display a custom SVG logo incorporating a violet flower motif.

#### Scenario: Logo in sidebar
- **WHEN** the sidebar renders
- **THEN** a violet flower SVG logo appears at the top of the sidebar
- **THEN** the logo is visually distinct and recognizable

#### Scenario: Favicon
- **WHEN** the page loads in a browser tab
- **THEN** the favicon displays the violet flower logo

### Requirement: Brand Name Display
The application SHALL display "Flowarr" as the brand name in the UI and page title.

#### Scenario: Page title
- **WHEN** any page renders
- **THEN** the HTML `<title>` contains "Flowarr"

#### Scenario: Sidebar brand
- **WHEN** the sidebar renders
- **THEN** "Flowarr" appears next to or below the logo

### Requirement: Auth Page Branding
The login and registration pages SHALL display a branded "powered by" flower accent.

#### Scenario: Login page accent
- **WHEN** the login page renders
- **THEN** a violet flower accent or decorative element is visible
- **THEN** the brand name "Flowarr" appears prominently
