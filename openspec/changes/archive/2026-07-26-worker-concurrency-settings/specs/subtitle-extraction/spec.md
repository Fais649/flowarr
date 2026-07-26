## ADDED Requirements

### Requirement: Concurrency Limit
ExtractSubtitlesJob SHALL respect the configured concurrency limit for its job type.

#### Scenario: At capacity
- **WHEN** the number of processing subtitle extraction executions is at or above the limit
- **THEN** the job releases back to the queue without starting ffmpeg or mkvmerge
- **WHEN** the number drops below the limit
- **THEN** the next picked-up job proceeds normally
