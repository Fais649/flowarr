## ADDED Requirements

### Requirement: Pause Support
Subtitle extraction SHALL support pause and resume via the same polling loop pattern used by other jobs.

#### Scenario: Pause via active stream
- **WHEN** `active_streams` cache key is greater than 0 and extraction is running
- **THEN** the running ffmpeg or mkvmerge process receives SIGSTOP
- **WHEN** `active_streams` returns to 0
- **THEN** the process receives SIGCONT

#### Scenario: Pause via manual override
- **WHEN** `media_processing_paused` cache key is set and extraction is running
- **THEN** the running process receives SIGSTOP
- **WHEN** the flag is cleared
- **THEN** the process receives SIGCONT

#### Scenario: Both pause conditions
- **WHEN** either `media_processing_paused` is set OR `active_streams > 0`
- **THEN** the process is paused via SIGSTOP
- **WHEN** both conditions are false
- **THEN** the process resumes via SIGCONT

## MODIFIED Requirements

### Requirement: Text-Based Extraction
Only text-based subtitle codecs SHALL be extracted to sidecar files.

#### Scenario: Extract SRT stream
- **WHEN** a subtitle stream has codec subrip, srt, ass, ssa, or webvtt
- **THEN** it is extracted to {dirname}/{basename}.{lang}.srt via ffmpeg
- **THEN** the ffmpeg process SHALL be managed via a polling loop with pause support

#### Scenario: Skip image-based streams
- **WHEN** a subtitle stream has a non-text codec (e.g., hdmv_pgs)
- **THEN** it is skipped with a warning

### Requirement: Strip Internal Subtitles
After extraction, all internal subtitle streams SHALL be removed from the container.

#### Scenario: mkvmerge strip
- **WHEN** all text subtitles are extracted
- **THEN** mkvmerge creates a new container without subtitle streams (-S flag)
- **THEN** the mkvmerge process SHALL be managed via a polling loop with pause support
- **THEN** the original file is replaced
