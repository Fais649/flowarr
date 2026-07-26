## Why

Two bugs in the scan pipeline: `ScanLibraryCommand` picks up every file including `.d.ts`/`.md`, then a logic fallthrough queues non-media files for subtitle conversion. And dispatched jobs sit forever in QUEUED because no queue worker is running on the `transcode`/`subtitle` queues.

## What Changes

- **De-duplicate scan commands**: Remove `ScanLibraryCommand` (old/buggy). Keep `ScanLibraries` which already uses the properly-filtered `ScannerService`.
- **Fix `ScannerService` subtitle filter**: `needsSubtitleConversion()` only checks extension — should also probe the file to verify it's actually a subtitle before queuing.
- **Add file extension filter constants**: Define shared media file extension lists so both video probing and subtitle detection use consistent allowlists.
- **Replace `ScanLibraryCommand` usage**: Any scheduled calls or references to `app:scan-library-command` switch to `scan:libraries`.
- **Queue worker integration**: Add a `queue:work` command to the dev setup / deployment docs, or add a long-running worker to the Sail services. Clean up stale `Execution` records that are stuck in QUEUED for non-media files.

## Capabilities

### New Capabilities
- `scan-file-filtering`: Shared extension allowlists and pre-probe file filtering for scanner pipeline

### Modified Capabilities
- `scanning`: Remove old `ScanLibraryCommand`, rely on `ScannerService` + `ScanLibraries` path. Add queue worker requirement.
- `queue-infrastructure`: Add queue worker runner for named queues (`transcode`, `subtitle`)

## Impact

- `app/Console/Commands/ScanLibraryCommand.php`: **BREAKING** — deleted in favor of `ScanLibraries`
- `app/Console/Commands/ScanLibraries.php`: No changes needed (already uses ScannerService)
- `app/Services/ScannerService.php`: Fix `needsSubtitleConversion()` to probe before assuming
- `app/Services/MediaProbeService.php` / `MediaProbeResult.php`: Add shared extension constants
- `app/Console/Commands/ScanLibraryCommand.php` references: Any scheduler or route using `app:scan-library-command` must point to `scan:libraries`
- Queue worker: Add `supervisord.conf` or Sail service entry for `queue:work --queue=transcode,subtitle`
