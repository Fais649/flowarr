## Context

Two in-progress changes have lingering tasks:
- `production-readiness`: 3 feature test files need to be written (settings profile, security, auth flows)
- `fix-scan-file-filtering-and-queue-processing`: 5 verification tasks need manual execution
Additionally, ScanSettings and WorkerSettings controllers have no test coverage.

## Goals / Non-Goals

**Goals:**
- Complete all pending tasks in `production-readiness` and `fix-scan-file-filtering-and-queue-processing`
- Add test coverage for settings controllers (profile, security, scan, worker)
- Archive both changes cleanly

**Non-Goals:**
- No new features or architecture changes
- No changes to production code (tests only)

## Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Auth flow tests | Smoke test login, register, forgot-password pages render | Covers critical auth paths; full E2E covered by browser tests |
| Settings test approach | Feature tests with HTTP assertions against controller routes | Matches existing test patterns in the codebase |
| Scan/Worker settings tests | Test the edit renders + update endpoint validates | Covers both GET and POST paths |

## Risks / Trade-offs

- **Verification tasks** (fix-scan-file-filtering) are manual: run scan, inspect Executions, run queue worker. Low risk.
