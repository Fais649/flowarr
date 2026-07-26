## Why

GitHub builds are failing — linter reports an unused variable, PHPStan baseline references deleted files. This blocks merging any changes and erodes confidence in CI. We need to fix the failures and add a process rule: never mark a task as complete until all three CI workflows (tests, linter, chromatic) pass green.

## What Changes

- Remove unused `cancelLabel` function from executions page
- Remove stale PHPStan baseline entries for deleted files (`ScanLibraryCommand.php`, old `WorkerSettingsController.php`)
- Regenerate PHPStan baseline to match current codebase
- Add build-gate requirement to CI pipeline spec: tasks must not be marked complete while builds are red

## Capabilities

### New Capabilities
- `build-gate-policy`: Process rule requiring green builds before task completion

### Modified Capabilities
- `ci-pipeline`: Add build gate requirement

## Impact

- `resources/js/pages/executions/index.tsx` — remove `cancelLabel`
- `phpstan-baseline.neon` — remove stale entries
- `openspec/specs/ci-pipeline/spec.md` — add build gate requirement
