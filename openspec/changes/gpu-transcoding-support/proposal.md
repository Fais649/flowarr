## Why

Flowarr's transcoding pipeline currently hardcodes `hevc_nvenc` as the GPU encoder with a simple on/off toggle. Self-hosters run on wildly different hardware — NVIDIA GPUs, AMD GPUs with VAAPI, Intel QuickSync, or no GPU at all. The app should detect available hardware at runtime, pick the best encoder, and gracefully fall back to software encoding when no GPU is available.

## What Changes

- **GPU hardware detection service** — probes available hardware at job runtime (NVIDIA via `nvidia-smi`, AMD/Intel VAAPI via `vainfo`, fallback to CPU)
- **Dynamic encoder selection** — picks the best available encoder: `hevc_nvenc` → `hevc_vaapi` → `libx265`
- **AMD VAAPI support** — adds `hevc_vaapi` encoder path with proper device selection (`/dev/dri/renderD128`)
- **Docker image** — includes VAAPI libraries `libva-drm2`, `libva2`, `mesa-va-drivers` for AMD/Intel support; NVIDIA driver is host-level (container uses `--gpus` or `nvidia-container-toolkit`)
- **Configuration** — ffmpeg encoder mapping in `config/services.php` with priority-ordered list for auto-detection
- **Docker compose** — adds device mount examples for AMD (default) and NVIDIA (commented out)
- **CI tests** — unit tests for hardware detection service with mocked ffmpeg probe results

## Capabilities

### New Capabilities
- `gpu-detection`: Runtime GPU hardware detection service with fallback chain

### Modified Capabilities
- `transcoding`: Encoder selection changed from static config to dynamic runtime detection with fallback chain; VAAPI encoder support added; hardware detection config added

## Impact

- **New files**: `app/Services/HardwareAccelerationService.php`, config entries in `config/services.php`
- **Modified files**: `app/Jobs/TranscodeMediaJob.php` (encoder resolution), `docker-compose.yml` (device mounts), `Dockerfile` (VAAPI libs)
- **New dependencies**: `libva-drm2`, `libva2`, `mesa-va-drivers` in Docker image (apt packages)
- **No breaking changes**: Existing `services.ffmpeg.use_nvenc` config still works; auto-detection is the new default
