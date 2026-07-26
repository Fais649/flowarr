# Transcoding

## Purpose

Transcode video files to HEVC with HDR-to-SDR tonemapping via ffmpeg, supporting both GPU (NVENC) and software (libx265) encoding.

## Requirements

### Requirement: HEVC Transcode Pipeline
The system SHALL transcode video files to HEVC with HDR-to-SDR tonemapping.

#### Scenario: Successful transcode
- **WHEN** a video file is processed by TranscodeMediaJob
- **THEN** ffmpeg runs with the configured video filter, codec, and preset
- **THEN** output is written as {basename}HEVC{ext}

#### Scenario: Software encoding fallback
- **WHEN** NVENC is unavailable or disabled
- **THEN** libx265 is used with medium preset

### Requirement: Configurable ffmpeg Path
The ffmpeg binary path SHALL be configurable.

#### Scenario: Custom binary path
- **WHEN** config('services.ffmpeg.bin') is set
- **THEN** that path is used instead of the default "ffmpeg"

### Requirement: Pause Support
Long-running transcodes SHALL support pause and resume.

#### Scenario: Pause via manual override
- **WHEN** `media_processing_paused` cache key is set
- **THEN** the running ffmpeg process receives SIGSTOP
- **WHEN** the flag is cleared
- **THEN** the process receives SIGCONT

#### Scenario: Pause via active stream
- **WHEN** `active_streams` cache key is greater than 0
- **THEN** the running ffmpeg process receives SIGSTOP
- **WHEN** `active_streams` returns to 0
- **THEN** the process receives SIGCONT

#### Scenario: Both pause conditions
- **WHEN** either `media_processing_paused` is set OR `active_streams > 0`
- **THEN** the process is paused via SIGSTOP
- **WHEN** both conditions are false
- **THEN** the process resumes via SIGCONT

### Requirement: Subtitle Job Pause Support
Subtitle conversion jobs SHALL also pause during active streams.

#### Scenario: Pause subtitle job on active stream
- **WHEN** `active_streams > 0`
- **THEN** the running mkvmerge process receives SIGSTOP
- **WHEN** `active_streams` returns to 0
- **THEN** the process receives SIGCONT

### Requirement: Error Handling
The system SHALL report transcode failures.

#### Scenario: ffmpeg failure
- **WHEN** ffmpeg exits with a non-zero code
- **THEN** a RuntimeException is thrown with the error output

### Requirement: Concurrency Limit
TranscodeMediaJob SHALL respect the configured concurrency limit for its job type.

#### Scenario: At capacity
- **WHEN** the number of processing transcode executions is at or above the limit
- **THEN** the job releases back to the queue without starting ffmpeg
- **WHEN** the number drops below the limit
- **THEN** the next picked-up job proceeds normally
