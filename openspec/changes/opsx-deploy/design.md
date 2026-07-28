## Context

Currently deploying requires running these steps manually:
1. `vendor/bin/sail artisan test` (or `php artisan test` in prod env)
2. `vendor/bin/sail bun run build` (frontend assets)
3. `docker build -t fais649/flowarr:latest .`
4. `docker push fais649/flowarr:latest`
5. `git add -A && git commit && git push`

Any failure at any step means the deploy is broken, and there's no systematic retry.

## Goals / Non-Goals

**Goals:**
- Define a single `/opsx-deploy` command an agent can execute
- Every failure is fixed and retried automatically
- Pipeline stops only when all steps pass

**Non-Goals:**
- Replace GitHub Actions CI (that runs on push separately)
- Add new infrastructure or monitoring
- Change the build process itself — only automate the existing one

## Decisions

### Sequential pipeline with per-step retry
Each step runs in order. If a step fails, the agent diagnoses the failure, fixes it, commits the fix, and retries from the failed step. This avoids re-running already-passed steps.

### Test before build
Tests run first. If tests fail, fix them before building the image. No point building an image with broken tests.

### Git push before Docker push
Push code first so the GHCR/GitHub Actions publish workflow can trigger. Then push the Docker image. If git push fails, Docker push is skipped.

## Risks / Trade-offs

- [Long-running pipeline] → Tests + build + push can take 5-15 minutes. Normal for a deploy pipeline.
- [Test flakiness] → If a test fails intermittently, the agent must distinguish flake from real failure. Recommendation: retry the failed test once before diagnosing.
- [Docker Hub rate limits] → If pushing fails due to rate limits, wait and retry.
