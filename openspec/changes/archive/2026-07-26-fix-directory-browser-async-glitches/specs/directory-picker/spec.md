## MODIFIED Requirements

### Requirement: Navigate tree
The directory tree SHALL fetch and display child directories when a node is expanded.

#### Scenario: Navigate tree
- **WHEN** the user clicks an expand arrow on a directory
- **THEN** the directory SHALL expand to show its children immediately from the pre-fetched tree data
- **THEN** no additional network request SHALL be made when expanding a directory within the initial fetch depth
- **THEN** empty directories SHALL show no expand arrow

#### Scenario: Bulk tree fetch on dialog open
- **WHEN** the directory browser dialog opens
- **THEN** a single request SHALL fetch up to 5 levels of subdirectories starting from the root
- **THEN** the dialog SHALL show a loading indicator while the tree is being fetched
- **THEN** once loaded, all directories within the depth limit SHALL be expandable without further network requests
