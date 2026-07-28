# GPU Detection

## Purpose

Probe available GPU hardware encoders at runtime using ffmpeg and select the optimal encoder for video transcoding.

## Requirements

### Requirement: GPU hardware detection
The system SHALL probe available GPU hardware encoders at runtime using ffmpeg.

#### Scenario: NVIDIA GPU with NVENC detected
- **WHEN** `ffmpeg -encoders` output includes `hevc_nvenc`
- **THEN** the system selects `hevc_nvenc` as the primary encoder

#### Scenario: AMD or Intel GPU with VAAPI detected
- **WHEN** `ffmpeg -encoders` output includes `hevc_vaapi` but NOT `hevc_nvenc`
- **THEN** the system selects `hevc_vaapi` as the primary encoder

#### Scenario: No GPU encoder available
- **WHEN** `ffmpeg -encoders` output includes neither `hevc_nvenc` nor `hevc_vaapi`
- **THEN** the system falls back to software encoding with `libx265`

#### Scenario: Explicit encoder override
- **WHEN** `services.ffmpeg.encoder` config is set to a specific value
- **THEN** the system uses that encoder directly without probing

### Requirement: Detection result logging
The system SHALL log the detected encoder at INFO level for observability.

#### Scenario: Encoder logged on detection
- **WHEN** the detection runs for the first time
- **THEN** an INFO log entry is written with the detected encoder name and source (probe or config)
