# Media Probing

## Purpose

Probe media files using ffprobe to determine file type, video codec, and subtitle presence for job dispatch decisions.

## Requirements

### Requirement: File Probing
The system SHALL probe media files and return structured results.

#### Scenario: Probe video file
- **WHEN** a video file is probed
- **THEN** the result includes the file extension, video codec name, and whether subtitles are present

#### Scenario: Probe subtitle file
- **WHEN** a subtitle-only file is probed
- **THEN** the result returns null video codec

### Requirement: File Type Classification
Probe results SHALL classify files as video or subtitle.

#### Scenario: Video detection
- **WHEN** a file has a video codec
- **THEN** isVideo() returns true

#### Scenario: Subtitle detection
- **WHEN** a file extension is ass, ssa, sub, idx, sup, or pgs
- **THEN** isSubtitle() returns true

### Requirement: Target Format Detection
Probe results SHALL indicate whether files already match target formats.

#### Scenario: Target subtitle format
- **WHEN** a file extension is srt
- **THEN** isTargetSubtitleExtension() returns true

#### Scenario: Target video encoding
- **WHEN** the video codec is hevc
- **THEN** isTargetVideoEncoding() returns true
