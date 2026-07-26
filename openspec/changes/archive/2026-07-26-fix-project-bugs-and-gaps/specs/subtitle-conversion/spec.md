## MODIFIED Requirements

### Requirement: Error Handling
The system SHALL handle conversion failures without data loss.

#### Scenario: Conversion failure
- **WHEN** ffmpeg fails to convert the file
- **THEN** the exception is logged and re-thrown
- **THEN** the original file SHALL NOT be deleted
