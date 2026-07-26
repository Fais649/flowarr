## Context

Flowarr runs ffmpeg transcoding jobs via `TranscodeMediaJob`. Currently a manual Cache flag (`media_processing_paused`) halts jobs, but there's no automated trigger. Jellyfin can send webhook notifications on `playback.start` and `playback.stop` events. The system needs to listen for these events and pause/resume all transcoding work.

## Goals / Non-Goals

**Goals:**
- Jellyfin sends webhook events → Flowarr pauses transcoding immediately
- Multiple simultaneous streams are handled correctly (counter, not boolean)
- Webhook endpoint is secured with a configurable shared secret
- Include `ConvertSubtitleJob` in pause/resume scope (not just transcoding)

**Non-Goals:**
- Per-file matching between Jellyfin and Flowarr paths
- Jellyfin user-aware filtering (pause for any user, any stream)
- Only pausing when the streamed file matches the file being worked on
- Dashboard UI showing active streams
- Webhook retry or delivery guarantees beyond what HTTP provides

## Decisions

### Decision: Counter-based pause instead of boolean flag

The existing `media_processing_paused` boolean is replaced with an `active_streams` counter. Each `playback.start` increments, each `playback.stop` decrements. Transcoding pauses when `active_streams > 0`. The controller guards against negative counters by never decrementing below 0 (stale `playback.stop` from a server restart).

The global `media_processing_paused` flag is preserved as a manual override — the job pauses if *either* `active_streams > 0` OR `media_processing_paused` is set.

### Decision: Dedicated `Cache::decrement` safety

Redis `decrement` can go negative. A positive guard (`max(0, current - 1)`) is applied in the controller. This handles edge cases like server restart during playback or duplicate stop events.

### Decision: Minimal webhook format — no per-file info needed

The webhook handler only reads the event type (`playback.start` / `playback.stop`). No filename or path parsing is needed since the app pauses for *any* stream. This eliminates the path-matching design problem identified in earlier sessions.

### Decision: Token-based auth via `X-Flowarr-Token` header

The webhook endpoint validates a bearer token configured in `services.jellyfin.webhook_token`. If the token is missing or empty in config, auth is disabled (for local-only setups).

### No Decoupled Alternative: Controller → Cache directly

The controller directly calls `Cache::increment`/`Cache::decrement`. No dedicated service class or event system — the endpoint is a thin adapter between the Jellyfin webhook plugin and the Redis pause mechanism. Event dispatchers add latency and indirection to a 5ms operation.

## Risks / Trade-offs

- **Race condition on restart**: If Jellyfin has active streams during Flowarr restart, the counter starts at 0. Jobs resume prematurely.  
  → Acceptable: streams will trigger new `playback.start` events (±8s Jellyfin webhook polling interval). Brief transcode window is low risk.
- **Stale `playback.stop` after server restart**: A `playback.stop` for a stream that never had a matching `playback.start` would go negative.  
  → Mitigated by `max(0, ...)` guard.
- **No queue backpressure**: Jobs check the flag in their polling loop. If a job just started a CPU-heavy operation before pause is set, there's a brief window of contention.  
  → Acceptable: SIGSTOP within ~3-5s polling interval.
