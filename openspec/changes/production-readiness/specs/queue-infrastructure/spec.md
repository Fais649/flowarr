## MODIFIED Requirements

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
