## MODIFIED Requirements

### Requirement: Dev Queue Worker
The `artisan dev` command SHALL start a queue worker that listens to all job queues.

#### Scenario: Dev worker listens to all queues
- **WHEN** running `artisan dev`
- **THEN** the queue worker SHALL listen to `transcode`, `subtitle`, and `default` queues
- **THEN** jobs in any of these queues SHALL be processed
