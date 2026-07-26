# Component Catalog

## Purpose

Provide an isolated visual development environment for React components, enabling agents and developers to verify rendering, styling, and behavior during development without navigating the application.

## Requirements

### Requirement: Storybook Dev Server
The system SHALL provide a Storybook dev server accessible during development via a dedicated npm script.

#### Scenario: Dev server starts
- **WHEN** the developer runs `bun run storybook`
- **THEN** a Storybook instance starts on a local port (default 6006)
- **THEN** it loads stories from configured glob patterns
- **THEN** it applies the project's Tailwind CSS and theme

### Requirement: Component Stories
Shared UI components SHALL have co-located story files that render each component with representative props.

#### Scenario: Story renders component
- **WHEN** a story file exists for a component (e.g., `Button.stories.tsx`)
- **THEN** the component renders in the Storybook canvas with its default props
- **THEN** controls are available to modify props interactively

#### Scenario: Themed rendering
- **WHEN** a story is viewed in Storybook
- **THEN** it applies the project's violet-flower theme (light and dark mode)
- **THEN** the theme toggle addon switches between light and dark

### Requirement: Story Conventions
Stories SHALL follow a documented convention for consistency and agent discoverability.

#### Scenario: Story structure
- **WHEN** an agent creates a new component
- **THEN** it creates a `.stories.tsx` file in the same directory
- **THEN** the story uses CSF 3 format with `meta` and at least one story export
- **THEN** the story imports from `@/components/ui/` using the project's path alias
