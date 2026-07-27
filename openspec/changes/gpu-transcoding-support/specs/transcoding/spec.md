## MODIFIED Requirements

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

### Requirement: VAAPI device configuration
The VAAPI render device path SHALL be configurable via environment variable.

#### Scenario: Custom device path
- **WHEN** `FFMPEG_VAAPI_DEVICE` env var is set
- **THEN** that path is used as the `-vaapi_device` argument instead of `/dev/dri/renderD128`

## ADDED Requirements

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
