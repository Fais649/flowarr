# Library Jobs

## Purpose

Configure which job types (transcode, extract subtitles, convert subtitles) are enabled for each library.

## Requirements

### Requirement: Job Type Configuration
Each library SHALL have zero or more enabled job types.

#### Scenario: Enable a job type
- **WHEN** a LibraryJob record is created linking a library to a job_id
- **THEN** that job type will run for qualifying files in that library

### Requirement: Job Class Resolution
Each LibraryJobId value SHALL resolve to a concrete job class.

#### Scenario: Resolve job class
- **WHEN** LibraryJobId::TRANSCODE_MEDIA->getJobClass() is called
- **THEN** it returns TranscodeMediaJob::class
- **WHEN** LibraryJobId::EXTRACT_SUBTITLES->getJobClass() is called
- **THEN** it returns ExtractSubtitlesJob::class
- **WHEN** LibraryJobId::CONVERT_SUBTITLE->getJobClass() is called
- **THEN** it returns ConvertSubtitleJob::class

### Requirement: Relationship to Executions
LibraryJobs SHALL produce Execution records as jobs are dispatched.

#### Scenario: Execution creation
- **WHEN** a job is dispatched for a LibraryJob
- **THEN** an Execution record is created linked to that LibraryJob

### Requirement: Job Toggle via UI
Users SHALL enable or disable job types for a library from the web UI.

#### Scenario: Toggle job on
- **WHEN** a user enables a job type on the library detail page
- **THEN** a LibraryJob record is created for that library and job type

#### Scenario: Toggle job off
- **WHEN** a user disables a job type on the library detail page
- **THEN** the corresponding LibraryJob record is deleted
- **THEN** existing Executions for that job are preserved
