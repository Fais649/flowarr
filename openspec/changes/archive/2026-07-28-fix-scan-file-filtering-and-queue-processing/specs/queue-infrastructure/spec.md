## ADDED Requirements

### Requirement: Queue Worker Process
A long-running queue worker process SHALL be available to process jobs from the `transcode` and `subtitle` queues.

#### Scenario: Worker listens on named queues
- **WHEN** the queue worker starts
- **THEN** it SHALL listen on the `transcode` queue with priority
- **THEN** it SHALL also listen on the `subtitle` queue
- **WHEN** a job is dispatched to the `transcode` queue
- **THEN** the worker SHALL pick it up and execute it
- **WHEN** a job is dispatched to the `subtitle` queue
- **THEN** the worker SHALL pick it up and execute it

#### Scenario: Worker runs continuously
- **WHEN** the queue worker is running
- **THEN** it SHALL stay alive and poll for new jobs indefinitely
- **WHEN** the worker is stopped
- **THEN** it SHALL be restarted automatically (e.g. via supervisor)

### Requirement: Dev Environment Worker
The local Sail development environment SHALL include a queue worker service for the `transcode` and `subtitle` queues.

#### Scenario: Sail service runs worker
- **WHEN** `vendor/bin/sail up -d` runs
- **THEN** a queue worker SHALL start listening on `transcode` and `subtitle` queues
- **WHEN** the Sail environment is shut down
- **THEN** the worker SHALL be stopped gracefully
