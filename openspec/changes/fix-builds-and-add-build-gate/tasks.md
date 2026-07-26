## 1. Fix Build Failures

- [ ] 1.1 Remove unused `cancelLabel` from `resources/js/pages/executions/index.tsx`
- [ ] 1.2 Remove stale PHPStan baseline entries for ScanLibraryCommand.php and old WorkerSettingsController.php
- [ ] 1.3 Regenerate PHPStan baseline
- [ ] 1.4 Verify all 3 CI workflows pass green on the fix commit

## 2. Add Build Gate Policy

- [ ] 2.1 Add build gate requirement to ci-pipeline spec
- [ ] 2.2 Archive change when all builds are green
