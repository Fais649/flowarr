## Context

Currently the production Docker image is tested by manually running the container and checking logs. There's no repeatable, automated e2e test that exercises the full pipeline: container start → library scan → job dispatch → job processing → completion.

## Goals / Non-Goals

**Goals:**
- Create a skill that can be invoked with `Run prod e2e test` or similar
- Use the existing `docker-compose.yml` stack
- Mount real test media files so transcode/subtitle jobs actually execute
- Assert all jobs complete successfully
- Clean up the stack after the test

**Non-Goals:**
- Modify the Docker image or compose files
- Add testing infrastructure beyond the skill file
- Run inside the Sail dev environment (uses production image)

## Decisions

### Uses the production docker-compose.yml
The skill runs `docker compose -f docker-compose.yml up -d` (with a test override for port mapping and test data volume). This tests the exact same image users will run.

### Test media files live on the host
The skill expects a `E2E_MEDIA_PATH` env var (default: `./e2e-media`) containing at least one video file (mkv/mp4) and one subtitle file (ass/ssa/srt). If the directory doesn't exist, the skill creates it with `ffmpeg` synthetic test files.

### Verification checks execution records
After the queue worker processes all jobs, the skill queries the database via `docker exec` to check that every execution for the test library has reached COMPLETED or FAILED status. Any stuck QUEUED or PROCESSING records mean the test failed.

## Decisions

### Sequential steps with status checks
Each step waits for the previous to complete. Docker healthchecks determine readiness before proceeding.

### Automatic cleanup
The skill always tears down the stack on completion (pass or fail) to avoid leaving containers running.

## Risks / Trade-offs

- [Test media files are large] → Use small synthetic files (5-second test clips). The e2e test doesn't need real content, just real formats.
- [ffmpeg availability on host] → The skill checks for ffmpeg before starting and falls back to downloading a small test file if needed.
- [Long runtime] → Full e2e can take 2-5 minutes depending on transcode speed. Normal.
