## 1. Delete Old Scan Path

- [x] 1.1 Delete `app/Console/Commands/ScanLibraryCommand.php` — the old buggy command with no file filtering
- [x] 1.2 Check scheduler (`app/Console/Kernel.php` or `routes/console.php`) for `app:scan-library-command` references and replace with `scan:libraries`
- [x] 1.3 Remove the `app:scan-library-command` signature from console kernel if registered there

## 2. Fix ScannerService Subtitle Logic

- [x] 2.1 Make `ScannerService::VIDEO_EXTENSIONS` a public constant or move to `MediaProbeService` for sharing
- [x] 2.2 Fix `ScannerService::needsSubtitleConversion()` to probe the file and verify subtitle content before dispatching, not just check extension
- [x] 2.3 Add `SUBTITLE_EXTENSIONS` constant to `MediaProbeService` if not already public

## 3. Add Queue Worker for Sail

- [x] 3.1 Add a `queue-worker` service entry in `docker-compose.yml` or a new Sail service that runs `php artisan queue:work --queue=transcode,subtitle --sleep=3 --tries=3`
- [ ] 3.2 Verify the worker starts with `vendor/bin/sail up -d` and processes jobs from both queues

## 4. Clean Up Stale Executions

- [x] 4.1 Create an Artisan command `scan:cleanup` that deletes Execution records with `status=QUEUED` where the file path extension is not in the media allowlist
- [ ] 4.2 Run the command to clean up existing stale records
- [ ] 4.3 Verify no legitimate queued items were deleted

## 5. Verify

- [ ] 5.1 Run `scan:libraries` and confirm no `.d.ts` or other non-media files appear in Execution table
- [ ] 5.2 Confirm subtitle conversion only triggers for actual subtitle files
- [ ] 5.3 Confirm queue worker picks up and processes jobs
