## ADDED Requirements

### Requirement: Media Extension Allowlist
The scanner SHALL only process files whose extensions match a defined allowlist of known media types. Non-media files (`.d.ts`, `.md`, `.txt`, `.json`, etc.) SHALL be excluded before probing.

#### Scenario: Only known media types are scanned
- **WHEN** the scanner encounters a file with extension `.mkv`, `.mp4`, `.avi`, `.mov`, `.m4v`, `.wmv`, `.ts`, or `.mts`
- **THEN** it SHALL probe the file for processing
- **WHEN** the scanner encounters a file with extension `.d.ts`, `.md`, `.txt`, `.json`, `.php`, `.yml`, or any non-media extension
- **THEN** it SHALL skip the file without probing

### Requirement: Shared Extension Constants
Media extension allowlists SHALL be defined as a public constant so all scanner components use the same list.

#### Scenario: Extension list is accessible
- **WHEN** any scanner component reads the video extension list
- **THEN** it SHALL reference the shared constant, not a private/hardcoded copy

### Requirement: Subtitle Extension Allowlist
The subtitle conversion filter SHALL use a shared allowlist of known subtitle extensions and SHALL verify the file is actually a subtitle before dispatching.

#### Scenario: Subtitle conversion validates probe result
- **WHEN** the scanner encounters a file that needs subtitle conversion
- **THEN** it SHALL probe the file to confirm it has subtitle content
- **THEN** if the probe does not confirm subtitle content, the file SHALL be skipped
