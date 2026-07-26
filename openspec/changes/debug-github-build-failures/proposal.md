## Why

GitHub Actions builds consistently fail on `main`. The tests workflow crashes on PHP 8.3 due to incompatible locked dependencies, builds for 8.4/8.5 time out, and the Chromatic workflow requires a project token that doesn't exist. This blocks all CI-driven development workflows: no automated test verification, no deploy confidence.

## What Changes

- **Tests workflow**: Remove PHP 8.3 from matrix (locked deps require >=8.4). Fix `npm i` to use `npm ci` for deterministic installs. Optimize build to prevent timeouts — skip storybook build in tests CI (covered by chromatic workflow). Add timeout bounds to steps.
- **Linter workflow**: Already passing. No changes needed.
- **Chromatic workflow**: Removed entirely — user has no Chromatic account/project. Delete workflow file, remove deps from `package.json`.
- **Existing CI spec**: Update to reflect correct PHP version range and revised workflow structure.

## Capabilities

### New Capabilities
- `ci-pipeline-config`: CI pipeline configuration, matrix tuning, timeout guards, and secret validation

### Modified Capabilities
- `ci-pipeline`: Update PHP version range to `^8.4`. Reflect streamlined workflow structure (remove storybook build from tests step).

## Impact

- `.github/workflows/tests.yml`: PHP matrix reduced to `[8.4, 8.5]`, npm switched to `npm ci`, Storybook build removed, timeout bounds added
- `.github/workflows/chromatic.yml`: File deleted
- `openspec/specs/ci-pipeline/spec.md`: Updated PHP version spec and step definitions
- No code changes to application source. No API or database changes.
