## Context

Flowarr's transcoding pipeline currently uses a static config flag `services.ffmpeg.use_nvenc` to pick between `hevc_nvenc` and `libx265`. This assumes:
- Either an NVIDIA GPU with NVENC is present, or no GPU at all
- AMD GPUs (VAAPI), Intel QuickSync, and other hardware are not accounted for
- The GPU type must be known at deploy time and configured manually

In practice, homelab GPU diversity is high — Steam Deck (AMD APU), Intel NUC (QuickSync), mining GPUs (NVIDIA). The app should do what the user expects: detect what's available and use it.

## Goals / Non-Goals

**Goals:**
- Single `HardwareAccelerationService` that probes available GPU hardware at runtime
- Priority-ordered fallback chain: NVENC → VAAPI → software (libx265)
- Each step runs a lightweight ffmpeg probe (`ffmpeg -encoders | grep hevc_nvenc`, `ffmpeg -encoders | grep hevc_vaapi`, etc.)
- Encoder mapping configurable in `config/services.php` with priority list
- Docker image includes VAAPI user-space drivers (`mesa-va-drivers`, `libva2`) for AMD/Intel
- Docker compose shows AMD device mount (`/dev/dri/renderD128`) enabled, NVIDIA (`--gpus`) commented out
- Existing `services.ffmpeg.use_nvenc` config still honoured as override (explicit opt-in)

**Non-Goals:**
- Not building CUDA/NVIDIA driver stacks into the Docker image — GPU drivers are host-level
- Not supporting Intel QSV separately from VAAPI (modern Intel uses VAAPI too)
- Not adding Vulkan-based encoders or FPGA accelerators
- Not changing the HDR tonemapping filter pipeline (separate concern)

## Decisions

| Decision | Choice | Alternatives Considered | Rationale |
|----------|--------|------------------------|-----------|
| Detection method | `ffmpeg -encoders` output parsing | `nvidia-smi`, `vainfo`, `ls /dev/dri` | ffmpeg is already a dependency; querying its compiled-in encoders tells us definitively what it can do regardless of host driver state. No extra deps. |
| Fallback order | NVENC → VAAPI → libx265 | Configurable priority list | NVIDIA NVENC is fastest and most mature. VAAPI covers AMD + Intel. libx265 always works. Config allows reordering if needed. |
| Encoder config format | Array of encoder IDs with metadata | Single encoder string | Array lets us define priority, flags, and device paths per encoder in one place. |
| VAAPI device | `/dev/dri/renderD128` | Auto-scan `/dev/dri/renderD*` | Render node 128 is the standard first GPU node. Users with multiple GPUs override via env var. |
| NVIDIA driver in image | Not included (host-level) | Install `nvidia-driver` in image | NVIDIA drivers must match host kernel; bundling them creates version lock. Standard Docker pattern. |
| Config override | `services.ffmpeg.encoder` env var | Remove existing `use_nvenc` | Backward compat. New config is `FFMPEG_ENCODER=hevc_vaapi` to force a specific encoder. |

## Detection Flow

```
TranscodeMediaJob::handle()
  │
  ├─ config('services.ffmpeg.encoder') set? ──▶ Use that encoder directly
  │
  └─ HardwareAccelerationService::detect()
       │
       ├─ 1. ffmpeg -encoders | grep hevc_nvenc ──▶ return 'hevc_nvenc'
       │
       ├─ 2. ffmpeg -encoders | grep hevc_vaapi ──▶ return 'hevc_vaapi'
       │
       └─ 3. Fallback ──▶ return 'libx265'
```

Each probe runs `ffmpeg -hide_banner -encoders 2>/dev/null | grep <encoder>` and checks exit code. The result is cached in memory for the job duration (no repeated probing per transcode).

## Risks / Trade-offs

- **[False negatives]** `ffmpeg -encoders` lists compiled-in support, not runtime availability. An NVIDIA driver load failure won't be caught until ffmpeg actually tries to use the encoder. Mitigation: The `resolveVideoFilter`/encoder selection runs first; ffmpeg is invoked, and if it fails with a GPU-related error, the job can fall back and retry with software encoding. For now, this is a manual restart with override config.
- **[VAAPI device path]** `/dev/dri/renderD128` is standard but not guaranteed. Mitigation: Configurable via `FFMPEG_VAAPI_DEVICE` env var.
- **[Image size]** Adding `mesa-va-drivers` and friends adds ~50 MB. Acceptable — they're user-space libraries, no kernel modules.
- **[Encoder quality]** `hevc_vaapi` produces lower quality per bitrate than `hevc_nvenc` or `libx265`. Mitigation: VAAPI is a fallback; users who care about quality over speed can set `FFMPEG_ENCODER=libx265`.

## Migration Plan

1. Deploy new Docker image with VAAPI libraries
2. Existing deployments with `services.ffmpeg.use_nvenc=true` continue to work (mapped to NVENC priority)
3. New deployments auto-detect GPU without any config change
4. AMD homelab users: add `/dev/dri/renderD128` device mount (shown in docker-compose.yml)

## Open Questions

1. Should the detection result be cached in Redis with a TTL to avoid probing on every transcode? (Deferred — per-job detection is cheap, ~5ms)
2. Should we log detected encoder at startup? (Yes — log at INFO level on first detection per worker)
