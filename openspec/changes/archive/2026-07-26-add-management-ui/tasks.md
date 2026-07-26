## 1. Shared Components

- [x] 1.1 Create `resources/js/components/data-table.tsx` — generic sortable, paginated table
- [x] 1.2 Create `resources/js/components/status-badge.tsx` — colored badge for status values
- [x] 1.3 Create `resources/js/components/filter-bar.tsx` — filter row with dropdowns and search
- [x] 1.4 Create `resources/js/components/empty-state.tsx` — empty state with illustration + CTA
- [x] 1.5 Create `resources/js/components/metric-card.tsx` — KPI card with label, value
- [x] 1.6 Update `app-sidebar.tsx` with navigation for Libraries, Executions, Workers

## 2. Libraries Backend

- [x] 2.1 Create `app/Http/Controllers/LibrariesController.php` with index, create, store, show, edit, update, destroy
- [x] 2.2 Create `app/Http/Requests/StoreLibraryRequest.php` — validate base_path (required, directory exists), scan_interval (integer, min 60)
- [x] 2.3 Create `app/Http/Requests/UpdateLibraryRequest.php` — same validation for update
- [x] 2.4 Add POST `/libraries/{library}/scan` route + `triggerScan()` method in controller
- [x] 2.5 Add POST `/libraries/{library}/toggle-job` route + `toggleJob()` method in controller
- [x] 2.6 Register all library routes in `routes/web.php`

## 3. Libraries Frontend

- [x] 3.1 Create `resources/js/pages/libraries/index.tsx` — table list with status badges, scan interval, action buttons
- [x] 3.2 Create `resources/js/pages/libraries/create.tsx` — form with base_path, scan_interval fields
- [x] 3.3 Create `resources/js/pages/libraries/[id]/index.tsx` — detail page with info section, job toggles, recent executions table
- [x] 3.4 Wire scan trigger button to POST endpoint with loading state

## 4. Executions Backend

- [x] 4.1 Create `app/Http/Controllers/ExecutionsController.php` with index, show, retry, cancel
- [x] 4.2 Create `app/Http/Requests/ExecutionFilterRequest.php` — validate status, library_id, per_page filters
- [x] 4.3 Register execution routes in `routes/web.php`

## 5. Executions Frontend

- [x] 5.1 Create `resources/js/pages/executions/index.tsx` — filterable, paginated table with status badges, library/job columns
- [x] 5.2 Add retry and cancel buttons with confirmation dialogs
- [x] 5.3 Wire filter bar to query params (status, library, page)

## 6. Workers Backend

## 6. Workers Backend

- [x] 6.1 Create `app/Http/Controllers/WorkersController.php` with index, show
- [x] 6.2 Register worker routes in `routes/web.php`

## 7. Workers Frontend

- [x] 7.1 Create `resources/js/pages/workers/index.tsx` — table with name, status, last heartbeat

## 8. Dashboard Upgrade

- [x] 8.1 Replace placeholder cards with real metric cards (library count, pending executions, failed today, queue depth)
- [x] 8.2 Add recent executions feed component
- [x] 8.3 Add library health overview section
- [x] 8.4 Add empty state when no libraries exist

## 9. Tests

- [x] 9.1 Write feature tests for LibrariesController (CRUD, scan trigger, toggle job)
- [x] 9.2 Write feature tests for ExecutionsController (index with filters, retry, cancel)
- [x] 9.3 Write feature tests for WorkersController (index, show)
