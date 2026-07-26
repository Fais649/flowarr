## ADDED Requirements

### Requirement: Root directory pre-loading
The directory browser SHALL pre-load the root directory contents when the dialog opens.

#### Scenario: Root pre-loaded on dialog open
- **WHEN** the directory browser dialog opens
- **THEN** the root directory SHALL automatically fetch and display its child directories
- **THEN** the user SHALL NOT need to click the expand arrow to load the root directory
