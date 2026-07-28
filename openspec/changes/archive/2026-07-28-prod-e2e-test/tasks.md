## 1. Write the E2E Test Skill

- [x] 1.1 Create `.pi/skills/prod-e2e-test/SKILL.md` with full e2e procedure: start stack → mount media → create library → scan → process jobs → verify → cleanup
- [x] 1.2 Add synthetic test media generation with `ffmpeg -f lavfi -i testsrc=duration=5:size=128x72 -c:v libx264 test.mkv` and a simple subtitle file
- [x] 1.3 Add library creation + scan trigger via curl/HTTP to the running app
- [x] 1.4 Add queue worker execution with bounded max-time/max-jobs
- [x] 1.5 Add verification step: query DB for execution statuses, assert all terminal
- [x] 1.6 Add cleanup step: always tear down stack, report pass/fail summary
