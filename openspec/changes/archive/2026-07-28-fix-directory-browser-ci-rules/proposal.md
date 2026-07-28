## Why

The directory picker (Browse button on library create/edit form) opens a modal but shows no directory tree — the API call silently fails and the error is swallowed, leaving the user with an empty dialog. Separately, the GitHub CI workflow keeps breaking because there's no formal check that tasks are only marked done when the pipeline is confirmed green, committed, and pushed.

## What Changes

- **Fix directory picker**: Debug and resolve the broken Browse → tree display flow. Add defensive error handling so failures are visible instead of silently swallowed.
- **Add CI completion gate**: Formalize a rule that implementation tasks are only marked complete after the CI workflow is run locally, confirmed green, committed, and successfully pushed.

## Capabilities

### New Capabilities
- `directory-picker`: Directory tree browsing for library path selection via the Browse modal
- `ci-completion-gate`: Workflow rule enforcing CI green-check before task completion

### Modified Capabilities
- *(none — no spec-level requirement changes)*

## Impact

- `resources/js/components/directory-browser.tsx` — error handling, edge case fixes
- `app/Http/Controllers/DirectoryController.php` — possible fix if backend issue found
- `openspec/config.yaml` — rules/doctrine configuration for CI gate
