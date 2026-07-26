## ADDED Requirements

### Requirement: Concurrency Limit
ConvertSubtitleJob SHALL respect the configured concurrency limit for its job type.

#### Scenario: At capacity
- **WHEN** the number of processing subtitle conversion executions is at or above the limit
- **THEN** the job releases back to the queue without starting ffmpeg
- **WHEN** the number drops below the limit
- **THEN** the next picked-up job proceeds normally
