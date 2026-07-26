# Subtitle Conversion

## Purpose

Convert non-SRT subtitle files to SRT format using ffmpeg.

## Requirements

### Requirement: SRT Conversion
The system SHALL convert subtitle files from any ffmpeg-readable format to SRT.

#### Scenario: Convert VTT to SRT
- **WHEN** a .vtt subtitle file is processed by ConvertSubtitleJob
- **THEN** ffmpeg converts it to {basename}.srt
- **THEN** the original file is deleted

#### Scenario: Skip SRT files
- **WHEN** the input file is already .srt
- **THEN** the job returns without processing

### Requirement: Error Handling
The system SHALL handle conversion failures without data loss.

#### Scenario: Conversion failure
- **WHEN** ffmpeg fails to convert the file
- **THEN** the exception is logged and re-thrown
- **THEN** the original file SHALL NOT be deleted

### Requirement: Concurrency Limit
ConvertSubtitleJob SHALL respect the configured concurrency limit for its job type.

#### Scenario: At capacity
- **WHEN** the number of processing subtitle conversion executions is at or above the limit
- **THEN** the job releases back to the queue without starting ffmpeg
- **WHEN** the number drops below the limit
- **THEN** the next picked-up job proceeds normally
