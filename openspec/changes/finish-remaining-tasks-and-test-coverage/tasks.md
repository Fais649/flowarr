## 1. Production-Readiness Test Tasks

- [ ] 1.1 Add feature test for settings profile controller (GET edit + PATCH update)
- [ ] 1.2 Add feature test for settings security controller (GET edit + password update)
- [ ] 1.3 Add feature tests for auth flows (login page renders, register page renders, forgot-password renders)

## 2. Missing Controller Test Coverage

- [ ] 2.1 Add feature test for ScanSettingsController (GET edit + POST update)
- [ ] 2.2 Add feature test for WorkerSettingsController (GET edit + POST update)

## 3. Scan File Filtering Verification

- [ ] 3.1 Run `vendor/bin/sail artisan scan:cleanup` to clean stale QUEUED records
- [ ] 3.2 Verify no legitimate queued items were deleted
- [ ] 3.3 Run `scan:libraries` and confirm no `.d.ts` or non-media files appear in executions
- [ ] 3.4 Confirm subtitle conversion only triggers for actual subtitle files
- [ ] 3.5 Run `queue:work --queue=transcode,subtitle` and confirm jobs process

## 4. Archive Completed Changes

- [ ] 4.1 Archive `production-readiness` change
- [ ] 4.2 Archive `fix-scan-file-filtering-and-queue-processing` change
- [ ] 4.3 Archive `finish-remaining-tasks-and-test-coverage` change
