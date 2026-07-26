## 1. Fix Tests Workflow

- [ ] 1.1 Remove PHP 8.3 from the matrix in `.github/workflows/tests.yml` — keep only `['8.4', '8.5']`
- [ ] 1.2 Switch `npm i` to `npm ci` in the "Install Node Dependencies" step
- [ ] 1.3 Remove the "Build Storybook" step from the tests workflow
- [ ] 1.4 Add `timeout-minutes: 15` to the `ci` job in tests workflow
- [ ] 1.5 Add `timeout-minutes: 10` to the linter job in `.github/workflows/lint.yml`

## 2. Fix Chromatic Workflow

- [ ] 2.1 Add token validation step before the Chromatic action — check `${{ secrets.CHROMATIC_PROJECT_TOKEN }}` is non-empty, fail with clear message if missing
- [ ] 2.2 Add `timeout-minutes: 10` to the chromatic job

## 3. Update CI Pipeline Spec

- [ ] 3.1 Update `openspec/specs/ci-pipeline/spec.md` with the delta from `specs/ci-pipeline/spec.md` — apply MODIFIED requirements (PHP version range, no storybook in tests) to the main spec
- [ ] 3.2 Push spec changes to git

## 4. Verify

- [ ] 4.1 Run `gh run list` and confirm new runs appear for tests and linter workflows
- [ ] 4.2 Confirm tests workflow passes on both PHP 8.4 and 8.5
- [ ] 4.3 Confirm chromatic workflow shows appropriate failure message (token missing)
- [ ] 4.4 Set `CHROMATIC_PROJECT_TOKEN` in GitHub repo secrets and re-run chromatic workflow
