## 1. Fix Tests Workflow

- [x] 1.1 Remove PHP 8.3 from the matrix in `.github/workflows/tests.yml` — keep only `['8.4', '8.5']`
- [x] 1.2 Switch `npm i` to `npm ci` in the "Install Node Dependencies" step
- [x] 1.3 Remove the "Build Storybook" step from the tests workflow
- [x] 1.4 Add `timeout-minutes: 15` to the `ci` job in tests workflow
- [x] 1.5 Add `timeout-minutes: 10` to the linter job in `.github/workflows/lint.yml`

## 2. Fix Chromatic Workflow

- [x] 2.1 Remove Chromatic workflow (`.github/workflows/chromatic.yml`) — no Chromatic project/token
- [x] 2.2 Remove Chromatic deps from `package.json` (`chromatic`, `@chromatic-com/storybook`, `chromatic` script)

## 3. Update CI Pipeline Spec

- [x] 3.1 Update `openspec/specs/ci-pipeline/spec.md` with the delta from `specs/ci-pipeline/spec.md` — apply MODIFIED requirements (PHP version range, no storybook in tests) to the main spec
- [x] 3.2 Push spec changes to git

## 4. Verify

- [x] 4.1 Run `gh run list` and confirm new runs appear for tests and linter workflows
- [x] 4.2 Confirm tests workflow passes on both PHP 8.4 and 8.5
- [x] 4.3 Confirm chromatic workflow shows appropriate failure message (token missing)
- [x] 4.4 Chromatic workflow removed — not applicable
