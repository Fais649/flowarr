## Context

Three CI workflows run on push/PR: linter (ESLint + Pint + Prettier), tests (Pest + PHPStan + TypeScript checks), chromatic (Storybook build). Currently workflows can fail without blocking task completion.

## Goals / Non-Goals

**Goals:**
- Fix all current build failures
- Add spec requirement that tasks only close when CI is green

**Non-Goals:**
- No changes to CI workflow configuration
- No adding new CI checks

## Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Build gate approach | Spec requirement in ci-pipeline, not CI config | Enforced by process, not by tooling. Simpler than blocked workflows. |
| Policy format | Added requirement to existing CI pipeline spec | Keep all CI-related requirements in one place. |
