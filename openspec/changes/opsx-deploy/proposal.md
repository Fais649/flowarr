## Why

Deploying Flowarr currently requires manually running tests, building the image, pushing to Docker Hub, and pushing to GitHub — multiple disjoint steps with no automation. If any step fails (test failure, build error, push rejection), there's no automatic retry or fix loop. This change creates a single `opsx-deploy` command that runs the full pipeline, fixes failures automatically, and only signals success when everything is green and pushed.

## What Changes

- Create `/opsx-deploy` as a repeatable deploy command/checklist
- Pipeline: run full test suite → build frontend → build Docker image → push to GitHub → push to Docker Hub
- If any step fails: auto-diagnose, fix, retry until green
- On success: confirm all commits pushed, image pushed to Docker Hub

## Capabilities

### New Capabilities
- `deploy-pipeline`: Automated deploy flow — test → build → push → retry on failure

### Modified Capabilities
- *(none)*

## Impact

- This is a process/automation change — no code changes to the application itself
- Documents the exact deploy sequence so any agent can execute it reliably
- Uses existing infrastructure: `vendor/bin/sail artisan test`, `docker build`, `git push`, `docker push`
