## Context

Two scanning paths exist:
1. **`ScanLibraryCommand`** (`app:scan-library-command`) — uses Symfony Finder with **no extension filtering**, picks up `.d.ts`, `.md`, `.txt`, etc. `getRequiredJobs()` has a fallthrough bug: non-video files that aren't `.srt` get queued for `CONVERT_SUBTITLE`.
2. **`ScanLibraries`** (`scan:libraries`) — uses `ScannerService` which filters by `VIDEO_EXTENSIONS`. But `needsSubtitleConversion()` only checks extension, not actual file content.

Neither path has a queue worker, so dispatched jobs sit forever in the `jobs` table with Execution records stuck in `QUEUED`.

## Goals / Non-Goals

**Goals:**
- Non-media files never enter the scan pipeline
- Subtitle conversion only triggers for actual subtitle files
- Remove duplicate `ScanLibraryCommand` to eliminate confusion
- Queue workers run automatically for `transcode` and `subtitle` queues
- Stale QUEUED executions for non-media files are cleaned up

**Non-Goals:**
- Rewriting either scanning implementation from scratch
- Changing the job dispatch or execution model
- Adding new job types or queue infrastructure

## Decisions

1. **Delete `ScanLibraryCommand`, keep only `ScanLibraries` + `ScannerService`**
   - `ScannerService` already has `collectMediaFiles()` with `VIDEO_EXTENSIONS` filtering. The old command is dead code introducing bugs. The newer path is cleaner and already tested.

2. **Fix `ScannerService::needsSubtitleConversion()` to actually probe**
   - Current: checks extension only (`['ass', 'ssa', 'webvtt', 'vtt', 'sub']`)
   - Fix: also probe the file to verify FFProbe identifies it as a subtitle stream
   - Alternative: keep extension-only but add the `isSubtitle()` check to `getRequiredJobs()` logic. But probing is more reliable (prevents false positives on eg `.sub` files that aren't actually subtitles).

3. **Add shared `MEDIA_EXTENSIONS` constant to `MediaProbeService`**
   - `ScannerService::VIDEO_EXTENSIONS` is hardcoded as a private constant. Move to `MediaProbeService` so `ScanLibraryCommand` (if kept) or future scanners can reuse it. Or better yet, since we're deleting `ScanLibraryCommand`, just make the existing `ScannerService` constant public.

4. **Add queue worker supervisor config**
   - Use a `supervisord.conf` shipped with the project or a Sail service override that runs `php artisan queue:work --queue=transcode,subtitle --sleep=3 --tries=3`
   - Default: one worker, listening on both queues with priority (transcode first, then subtitle)

5. **Clean up stale executions**
   - Add a migration or Artisan command to delete Execution records with `status=QUEUED` where the file path's extension is not in the media list. Run once as part of the fix.

## Risks / Trade-offs

- **Deleting ScanLibraryCommand**: If any external script calls `app:scan-library-command`, it breaks. Mitigation: the proposal replaces it with `scan:libraries`. Check the scheduler.
- **Adding supervisor config**: Requires `supervisor` package on the server. Mitigation: document in deployment guide. For dev, add a Sail service entry.
- **Stale cleanup could delete legitimate queued items**: Mitigation: only delete where extension is provably non-media (not in video OR subtitle extension lists). Keep items where extension is ambiguous.
