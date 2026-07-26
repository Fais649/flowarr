# Test Data Management

## Purpose

Provide realistic media files and a restore script for end-to-end pipeline testing.

## Requirements

### Requirement: Test Data Directory
The system SHALL have a `test-data/` directory at the project root level containing sample media files.

#### Scenario: Test data exists
- **WHEN** cloning the project and running `restore-test-data.sh`
- **THEN** `test-data/` contains at least one `.mkv` file with h.264 video and text subtitles
- **THEN** `test-data/` contains at least one `.srt` subtitle file
- **THEN** `test-data/` contains a `.bak` subdirectory with the original backup

### Requirement: Restore Script
The system SHALL provide `restore-test-data.sh` that restores `test-data/` to its original state.

#### Scenario: Restore test data
- **WHEN** running `restore-test-data.sh`
- **THEN** all files in `test-data/` are replaced from the `.bak` backup
- **THEN** any modifications or new files are discarded
