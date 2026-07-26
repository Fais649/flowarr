## Why

The application has models, migrations, jobs, and a scanner — but no management UI. Users have no way to add libraries, configure jobs, monitor executions, or see worker status. The dashboard is placeholder blocks. Without a UI, the app is unusable as a management tool.

## What Changes

- Add **Libraries CRUD**: list, create, edit, delete library paths with status and scan interval
- Add **Library detail page**: status controls, enabled job toggles, recent executions per library
- Add **Executions list**: paginated table with status filtering, search, sort by date
- Add **Execution detail**: log output, retry/cancel actions
- Add **Workers list**: table showing name, status, last heartbeat
- Upgrade **Dashboard**: KPI cards (library count, pending executions, failed today), recent executions feed, library health overview
- Update sidebar navigation to include Libraries, Executions, Workers
- Add form request validation classes for all CRUD operations
- Update existing specs for `library-management`, `library-jobs`, `execution-tracking` with UI requirements

## Capabilities

### New Capabilities

- `management-ui`: Full CRUD management pages for libraries, executions, and workers

### Modified Capabilities

- `library-management`: Add requirements for library CRUD via web UI
- `library-jobs`: Add requirements for toggling job types via web UI
- `execution-tracking`: Add requirements for viewing and filtering executions via web UI

## Impact

- `routes/web.php` — new library, execution, worker routes
- `app/Http/Controllers/` — new controllers (LibrariesController, ExecutionsController, WorkersController)
- `app/Http/Requests/` — new form request validation classes
- `resources/js/pages/` — new page components (libraries/, executions/, workers/)
- `resources/js/components/` — reusable data table, status badge, filter bar components
- `resources/js/components/app-sidebar.tsx` — updated navigation
- `app/Models/Worker.php` — may need additional scopes or accessors
- `openspec/specs/library-management/spec.md` — updated with CRUD requirements
- `openspec/specs/library-jobs/spec.md` — updated with UI toggle requirements
- `openspec/specs/execution-tracking/spec.md` — updated with view/filter requirements
