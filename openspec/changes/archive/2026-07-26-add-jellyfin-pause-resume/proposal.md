## Why

When someone streams from Jellyfin, GPU-intensive transcoding jobs compete for resources and cause buffering. The app needs to detect streams starting and pause all transcoding work until they stop. This is a core requirement (README.md:14) — the whole reason Flowarr exists is to pre-transcode media so real-time transcoding isn't needed.

## What Changes

- Add `JellyfinWebhookController` with `playbackStart` and `playbackStop` endpoints
- Add webhook route to `routes/web.php` behind configurable token auth
- Replace global `media_processing_paused` boolean with `active_streams` counter
- Update `TranscodeMediaJob` pause loop to check both `active_streams > 0` and `media_processing_paused`
- Add Jellyfin configuration to `config/services.php`
- Add webhook setup instructions to `README.md`

## Capabilities

### New Capabilities

- `jellyfin-integration`: Webhook receiver for Jellyfin playback events with counter-based pause/resume

### Modified Capabilities

- `transcoding`: Pause requirement — change from single Cache flag to active streams counter + global flag

## Impact

- `app/Http/Controllers/JellyfinWebhookController.php` — new controller
- `routes/web.php` — new webhook routes
- `app/Jobs/TranscodeMediaJob.php` — update pause loop logic
- `config/services.php` — add jellyfin config section
- `README.md` — webhook setup instructions
- `openspec/specs/transcoding/spec.md` — updated pause requirement
