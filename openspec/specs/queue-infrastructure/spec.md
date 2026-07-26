# Queue Infrastructure

## Purpose

Provide a RabbitMQ-backed job queue for asynchronous media processing with per-job-type routing, retry, and failure handling.

## Requirements

### Requirement: RabbitMQ Connection
The system SHALL connect to RabbitMQ for queue processing.

#### Scenario: Configured connection
- **WHEN** QUEUE_CONNECTION is set to "rabbitmq"
- **THEN** jobs are dispatched via the RabbitMQ driver
- **THEN** connection uses configured host, port, credentials, and vhost

### Requirement: Per-Type Queue Routing
Jobs SHALL be routed to queues based on their type, with queue names configurable via `config/queue.php`.

#### Scenario: Transcode queue
- **WHEN** a TranscodeMediaJob is dispatched
- **THEN** it SHALL go to the "transcode" queue
- **WHEN** an ExtractSubtitlesJob is dispatched
- **THEN** it SHALL go to the "subtitle" queue
- **WHEN** a ConvertSubtitleJob is dispatched
- **THEN** it SHALL go to the "subtitle" queue
- **WHEN** any other job is dispatched
- **THEN** it SHALL go to the "default" queue

#### Scenario: Queue configuration
- **WHEN** reading `config('queue.queues.transcode')`
- **THEN** the queue name SHALL be configurable
- **WHEN** reading `config('queue.queues.subtitle')`
- **THEN** the queue name SHALL be configurable

### Requirement: Job-to-Queue Mapping
The `LibraryJobId` enum SHALL provide a method to return the target queue for each job type.

#### Scenario: Queue method on LibraryJobId
- **WHEN** calling `LibraryJobId::TRANSCODE_MEDIA->getQueue()`
- **THEN** it SHALL return the configured transcode queue name
- **WHEN** calling `LibraryJobId::EXTRACT_SUBTITLES->getQueue()`
- **THEN** it SHALL return the configured subtitle queue name
- **WHEN** calling `LibraryJobId::CONVERT_SUBTITLE->getQueue()`
- **THEN** it SHALL return the configured subtitle queue name

### Requirement: Retry and Failure Handling
Failed jobs SHALL be retried according to a configurable policy.

#### Scenario: Job failure
- **WHEN** a job fails
- **THEN** it is retried up to the configured maximum attempts
- **THEN** after exhausting retries, it moves to the failed jobs table

### Requirement: DispatchableJob Interface
All job classes SHALL implement the `DispatchableJob` interface.

#### Scenario: Job implements interface
- **WHEN** a job class is dispatched via `LibraryJobId::getJobClass()`
- **THEN** the class implements `App\Jobs\Contracts\DispatchableJob`

### Requirement: Dev Queue Worker
The `artisan dev` command SHALL start a queue worker that listens to all job queues.

#### Scenario: Dev worker listens to all queues
- **WHEN** running `artisan dev`
- **THEN** the queue worker SHALL listen to `transcode`, `subtitle`, and `default` queues
- **THEN** jobs in any of these queues SHALL be processed
