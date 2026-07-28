## Why

There's no automated way to verify the production Docker image actually works end-to-end. Unit and feature tests cover individual components, but they don't test the real production container with actual media files. A broken production image (missing ffmpeg, wrong permissions, bad config) can slip through. This change creates a skill that spins up the production stack, mounts test media, creates a library, runs all job types, and confirms they succeed.

## What Changes

- Create `.pi/skills/prod-e2e-test/SKILL.md` — a skill an agent can invoke to run the full e2e test
- The skill:
  1. Starts the production Docker stack (postgres + redis + app)
  2. Mounts a test-data directory with known media files under `/media`
  3. Creates a library pointing at that directory
  4. Triggers a scan to queue jobs
  5. Runs the queue worker to process all jobs
  6. Verifies each job reached a terminal state (completed or failed)
  7. Reports pass/fail and cleans up the stack

## Capabilities

### New Capabilities
- `prod-e2e-test`: End-to-end test that validates the production Docker image against real media files

### Modified Capabilities
- *(none)*

## Impact

- New skill file at `.pi/skills/prod-e2e-test/SKILL.md`
- Requires test media files to exist at a known path (configurable via env or default)
- No changes to application code, Dockerfile, or infrastructure
