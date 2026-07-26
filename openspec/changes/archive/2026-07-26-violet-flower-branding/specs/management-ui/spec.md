## ADDED Requirements

### Requirement: Themed Rendering
All management UI pages SHALL render with the violet flower color palette applied to all shadcn components.

#### Scenario: Components use theme variables
- **WHEN** any management UI page (Dashboard, Libraries, Executions, Workers) renders
- **THEN** all shadcn components (buttons, tables, badges, cards, inputs, dialogs) use the violet theme colors
- **THEN** status badges remain distinguishable (green for completed, red for failed, etc.) but may use muted violet tones for neutral states
