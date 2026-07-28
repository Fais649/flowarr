## Why

The Workers tab is essentially a read-only list of registered instances with no management capability, while actual worker configuration lives in a separate config tab. This split is confusing — users should manage workers where they see them. Libraries currently toggle job types (generic enums) rather than specific workers. And the executions list can only retry or cancel individual items, with no manual lifecycle control or batch operations. The queue-based automatic processing works, but users need on-demand control.

## What Changes

- **Remove the config/workers tab** — its functionality (concurrency per job type, global pause) moves into the Workers tab
- **Add Worker management UI**: "Add Worker" button, choose job type on creation, details view with start/pause/resume/stop controls, list-view bulk controls
- **Add "all workers" actions** in the workers list: Start All, Pause All, Resume All, Stop All
- **Library detail view**: replace job-type toggles with worker selection (choose which workers are enabled for a given library)
- **Executions list**: add manual start/stop/pause/resume for single or selected executions, add delete for one or more executions, all working independently of the queue

## Capabilities

### New Capabilities
- `worker-management`: CRUD workers, per-worker start/pause/resume/stop, bulk actions, replaces config/workers tab
- `library-worker-selection`: Choose which workers are enabled per library in library detail view
- `execution-lifecycle-control`: Manual start/stop/pause/resume and delete for single or batch executions

### Modified Capabilities
- *(none — turning config tab into workers tab is an implementation change, not a spec change)*

## Impact

- Remove `resources/js/pages/config/workers.tsx` and its routes
- Rewrite `resources/js/pages/workers/index.tsx` — add worker creation, bulk actions
- Rewrite `resources/js/pages/workers/[id]/index.tsx` — add controls, concurrency settings
- Extend `app/Http/Controllers/WorkersController.php` — add create, update lifecycle methods
- Modify `resources/js/pages/libraries/[id]/index.tsx` — replace job type toggles with worker selection
- Extend `app/Http/Controllers/LibrariesController.php` — worker-library association
- Extend `app/Http/Controllers/ExecutionsController.php` — add start, pause, resume, batch delete
- Extend `resources/js/pages/executions/index.tsx` — add lifecycle buttons, checkboxes for batch, delete action
- Add `app/Models/Worker.php` migration for job_type column
