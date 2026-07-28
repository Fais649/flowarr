# Transcoding

## Purpose

Transcode video files to HEVC with HDR-to-SDR tonemapping via ffmpeg, supporting both GPU (NVENC) and software (libx265) encoding.

## Requirements

### Requirement: HEVC Transcode Pipeline
The system SHALL transcode video files to HEVC with HDR-to-SDR tonemapping via ffmpeg, using the best available hardware encoder detected at runtime.

#### Scenario: Successful transcode with detected encoder
- **WHEN** a video file is processed by TranscodeMediaJob
- **THEN** the encoder is resolved by HardwareAccelerationService based on available hardware
- **THEN** ffmpeg runs with the configured video filter, resolved codec, and matching preset
- **THEN** output is written as {basename}HEVC{ext}

#### Scenario: NVIDIA NVENC encoding
- **WHEN** `hevc_nvenc` is the resolved encoder
- **THEN** ffmpeg uses `-c:v hevc_nvenc -preset p4`

#### Scenario: AMD VAAPI encoding
- **WHEN** `hevc_vaapi` is the resolved encoder
- **THEN** ffmpeg uses `-c:v hevc_vaapi -vaapi_device /dev/dri/renderD128` (or configured device path)
- **THEN** the video filter includes `format=nv12,hwupload` for VAAPI surface upload

#### Scenario: Software encoding fallback
- **WHEN** no hardware encoder is detected
- **THEN** `libx265` is used with medium preset
- **THEN** no device-specific flags are passed

#### Scenario: Explicit encoder override
- **WHEN** `services.ffmpeg.encoder` is set to `libx265`
- **THEN** software encoding is used regardless of available hardware

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

### Requirement: VAAPI device configuration
The VAAPI render device path SHALL be configurable via environment variable.

#### Scenario: Custom device path
- **WHEN** `FFMPEG_VAAPI_DEVICE` env var is set
- **THEN** that path is used as the `-vaapi_device` argument instead of `/dev/dri/renderD128`

### Requirement: Docker image includes VAAPI libraries
The Docker image SHALL include user-space VAAPI libraries for AMD and Intel hardware encoding support.

#### Scenario: VAAPI libraries present
- **WHEN** inspecting the runtime image
- **THEN** packages `libva2`, `libva-drm2`, and `mesa-va-drivers` SHALL be installed

#### Scenario: Software encoding still works without GPU
- **WHEN** the container runs without `/dev/dri` device mounted
- **THEN** `ffmpeg` SHALL still fall back to software encoding with `libx265`

### Requirement: Docker compose with GPU device mounts
The docker-compose.yml SHALL include device mount examples for both AMD and NVIDIA GPUs.

#### Scenario: AMD GPU mount enabled
- **WHEN** reading the compose file
- **THEN** the `flowarr` service SHALL have `devices: - /dev/dri:/dev/dri` listed and uncommented

#### Scenario: NVIDIA GPU mount documented
- **WHEN** reading the compose file
- **THEN** a commented-out block SHALL show how to configure NVIDIA GPU access with `deploy.resources.reservations.devices`
