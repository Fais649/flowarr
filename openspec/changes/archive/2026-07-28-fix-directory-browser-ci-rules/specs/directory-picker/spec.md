## ADDED Requirements

### Requirement: Browse modal shows directory tree
The directory browser SHALL display a hierarchical directory tree when the Browse button is clicked.

#### Scenario: Tree renders on first open
- **WHEN** the user clicks "Browse" on the library create/edit form
- **THEN** a modal dialog SHALL open
- **THEN** the modal SHALL fetch the directory tree from the backend
- **THEN** the modal SHALL display the directory hierarchy

#### Scenario: Loading state shown during fetch
- **WHEN** the directory tree is being fetched
- **THEN** a loading spinner SHALL be displayed in the modal body
- **THEN** the empty state SHALL NOT be shown

### Requirement: Visible error feedback
If the directory API call fails, the modal SHALL display a human-readable error message instead of silently showing an empty state.

#### Scenario: API returns non-200
- **WHEN** the directory fetch returns a non-200 HTTP status
- **THEN** the modal SHALL display an error message indicating the failure
- **THEN** the error message SHALL include the HTTP status code

#### Scenario: Network error
- **WHEN** the fetch request fails due to network error
- **THEN** the modal SHALL display a generic connection error message

### Requirement: Leaf nodes handle missing children
The tree directory node component SHALL handle the case where `children` is not present in the response data.

#### Scenario: Node with undefined children
- **WHEN** a directory node has no `children` key in the API response
- **THEN** the node SHALL be rendered as a leaf (no expand chevron)
- **THEN** no JavaScript error SHALL be thrown
