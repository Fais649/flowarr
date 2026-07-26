# Transcoding

## MODIFIED Requirements

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

## ADDED Requirements

### Requirement: Subtitle Job Pause Support

Subtitle conversion jobs SHALL also pause during active streams.

#### Scenario: Pause subtitle job on active stream
- **WHEN** `active_streams > 0`
- **THEN** the running mkvmerge process receives SIGSTOP
- **WHEN** `active_streams` returns to 0
- **THEN** the process receives SIGCONT
