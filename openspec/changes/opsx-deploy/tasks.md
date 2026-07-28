## 1. Define the Deploy Pipeline

- [ ] 1.1 Write the deploy pipeline procedure as a skill file at `.pi/skills/opsx-deploy/SKILL.md` — the single source of truth an agent follows when the user says `/opsx-deploy`
- [ ] 1.2 Pipeline step 1: Run `vendor/bin/sail artisan test --compact` — if fail, diagnose, fix, retry
- [ ] 1.3 Pipeline step 2: Run `vendor/bin/sail bun run build` — if fail, fix, retry
- [ ] 1.4 Pipeline step 3: Run `docker build -t fais649/flowarr:latest .` — if fail, fix, retry
- [ ] 1.5 Pipeline step 4: Run `git add -A && git commit -m "deploy: <description>" && git push` — if fail, fix, retry
- [ ] 1.6 Pipeline step 5: Run `docker push fais649/flowarr:latest` — if fail, fix, retry
- [ ] 1.7 Add the skill to `available_skills` in the project's agent config so it's discoverable
