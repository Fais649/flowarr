## 1. Worker Model & Migration

- [x] 1.1 Add `job_type` (string-backed enum) and `concurrency` (int) columns to workers table via new migration
- [x] 1.2 Update `Worker` model with new fillable fields and `LibraryJobId` cast for `job_type`
- [x] 1.3 Update `WorkerFactory` with `job_type` and `concurrency` defaults
- [x] 1.4 Create seeder to migrate existing global concurrency settings into per-worker defaults

## 2. Worker Management Backend

- [x] 2.1 Add `store` method to `WorkersController` — create worker with name + job_type + concurrency
- [x] 2.2 Add `update` method to `WorkersController` — edit name, concurrency
- [x] 2.3 Add `destroy` method to `WorkersController` — delete a worker
- [x] 2.4 Add lifecycle action methods: `start`, `pause`, `resume`, `stop` — one per worker
- [x] 2.5 Add bulk lifecycle actions: `startAll`, `pauseAll`, `resumeAll`, `stopAll`
- [x] 2.6 Register new routes for worker CRUD and lifecycle actions

## 3. Worker Management Frontend

- [x] 3.1 Rewrite `workers/index.tsx` — add "Add Worker" button + creation modal, per-row lifecycle buttons (Start/Pause/Resume/Stop), bulk action buttons (Start All / Pause All / Resume All / Stop All), concurrency settings section
- [x] 3.2 Rewrite `workers/[id]/index.tsx` — add lifecycle controls, editable name and concurrency, show job type and heartbeat
- [x] 3.3 Remove `config/workers.tsx` and its route — concurrency + pause settings now live in Workers tab

## 4. Library Worker Selection

- [x] 4.1 Modify `LibrariesController::show` — pass available workers to the library detail view
- [x] 4.2 Add `toggleWorker` method to `LibrariesController` or a new `LibraryWorkersController`
- [x] 4.3 Rewrite "Job Toggles" card in `libraries/[id]/index.tsx` — show worker list with enable/disable toggles

## 5. Execution Lifecycle Control

- [x] 5.1 Add `start`, `pause`, `resume` methods to `ExecutionsController` (existing `cancel` covers stop)
- [x] 5.2 Add batch action endpoints: `batchStart`, `batchPause`, `batchResume`, `batchStop`, `batchDelete`
- [x] 5.3 Add `destroy` method to `ExecutionsController` — delete a single execution
- [x] 5.4 Rewrite `executions/index.tsx` — add checkbox column, batch action toolbar, Start/Pause/Resume/Stop/Delete buttons per row
- [x] 5.5 Register new routes for execution lifecycle and batch actions

## 6. Tests

- [x] 6.1 Add/update feature tests for WorkersController CRUD + lifecycle
- [x] 6.2 Add feature tests for execution lifecycle actions (start/pause/resume/delete/batch)
- [x] 6.3 Add/update browser tests for workers, library-worker, and executions UI
