## Context

The current workers system has three separate surfaces that need consolidation:

1. **Config → Workers** (`config/workers.tsx`): Sets concurrency limits per job type (`transcode_media`, `extract_subs`, `convert_sub`) and a global pause toggle. This is a settings form, not a worker manager.

2. **Workers tab** (`workers/index.tsx`, `workers/[id]/index.tsx`): Shows a read-only list of Worker model instances (name + timestamps). No lifecycle control, no type association.

3. **Library detail** (`libraries/[id]/index.tsx`): Has "Job Toggles" card that enables/disables job types via the `LibraryJobId` enum. There's no concept of selecting specific workers.

Worker model currently has only a `name` field. ExecutionStatus has `queued, processing, completed, stopped, paused, failed` — but the UI only exposes retry (for failed) and cancel (for queued/processing).

## Goals / Non-Goals

**Goals:**
- Unify worker config + worker list into a single Workers tab with full CRUD and lifecycle control
- Let users create workers by selecting a job type (not just a name)
- Provide per-worker start/pause/resume/stop controls in both list and detail views
- Add bulk "Start/Pause/Resume/Stop All" in workers list
- Replace library job-type toggles with worker selection
- Add execution lifecycle control (start/pause/resume/stop) and delete, single and batch

**Non-Goals:**
- Change the queue backend or how jobs are dispatched
- Rewrite the actual job execution engine
- Add scheduling or cron-like functionality
- Change the data model for LibraryJob or Execution (new fields only)

## Decisions

### Worker model gains a `job_type` column
Instead of just a `name`, every Worker has an associated `LibraryJobId` (e.g., `transcode_media`). This replaces the concurrency-per-job-type settings model. Concurrency becomes a per-worker setting, not a global one.

### Config workers tab is removed, settings move into Workers tab
The concurrency inputs and pause toggle move into the Workers list page (as a collapsible settings section or inline card). The dedicated config/workers route and page are deleted.

### Execution lifecycle uses the existing status machine
`ExecutionStatus` already has PAUSED and STOPPED states. Start transitions QUEUED → PROCESSING (or PAUSED → PROCESSING), Stop transitions → STOPPED, Pause transitions → PAUSED, Resume → returns to the previous running state. Delete removes the record entirely (only allowed for terminal states).

### Batch selection uses checkboxes
Executions table gets a checkbox column and a batch action toolbar. Workers list gets bulk action buttons (Start All, etc.) that send a single POST per action type.

## Risks / Trade-offs

- [Concurrency becomes per-worker] → Migrating existing global concurrency settings to per-worker defaults needs a seeder. Existing config values won't auto-migrate.
- [Execution lifecycle actions bypass queue] → Manual start/pause/resume directly mutates the Execution status without going through the queue worker. This is intentional for manual override but could cause confusion if a queue worker picks up the same job.
- [Batch actions are synchronous] → Each action triggers N HTTP requests (or one batch endpoint). One batch endpoint is preferred but means backend needs a bulk-action route.
