## Context

The app has all backend models and infrastructure but no management interface. Users interact through the starter kit's auth pages and a placeholder dashboard. The sidebar only links to Dashboard. The existing app-shell layout and shadcn/ui primitives provide the foundation.

## Goals / Non-Goals

**Goals:**
- Full CRUD for libraries: create, edit, delete, trigger scan
- Library detail page with job toggles + per-library execution feed
- Global executions list with status filtering and pagination
- Workers list showing online/idle status
- Dashboard with real metrics (library count, pending executions, queue depth)
- Navigation updated with Libraries, Executions, Workers sections

**Non-Goals:**
- Real-time updates (polling or SSE — future concern)
- Worker heartbeat registration (Phase 4 — separate change)
- Job ↔ Execution status wiring (Phase 1 — already planned)
- Queue routing configuration

## Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Page layout | Table list + detail page per entity | Consistent pattern: list shows all records, click to detail. Libraries also need a create/edit form |
| Library form | Inline on detail page (not modal) | Libraries have few fields. Detail page has config sections (info, jobs, executions). Cleaner than a separate edit route |
| Library job toggles | Toggle switches on detail page | Each job type is on/off. POST to update toggles sends diff. No separate form needed |
| Execution filtering | Query params: `?status=failed&library=1&page=2` | Clean URLs, shareable, Inertia handles naturally. Filter bar component with status dropdown + library select |
| Dashboard layout | KPI row (4 cards) + recent executions table + library health cards | Matches the 3-column placeholder grid already on the page |
| Controllers | Resource controllers for libraries, dedicated for executions/workers | Libraries are full CRUD. Executions are read-only + retry/cancel. Workers are read-only |
| Form validation | Form Request classes | Follows existing patterns, keeps controllers clean, reusable |
| Sidebar structure | Grouped sections with labels | Libraries section, Monitoring section (Executions, Workers), Settings section (existing) |

## Page Architecture

```
resources/js/pages/
├── dashboard.tsx                      (upgrade existing)
├── libraries/
│   ├── index.tsx                      (table list)
│   ├── create.tsx                     (create form)
│   └── [id]/
│       └── index.tsx                  (detail + config + recent executions)
├── executions/
│   └── index.tsx                      (filterable table list)
└── workers/
    └── index.tsx                      (table list)
```

## New Shared Components

- `data-table.tsx` — Generic sortable, paginated table with slots for header and row rendering
- `status-badge.tsx` — Colored badge for library/execution/worker status values
- `filter-bar.tsx` — Horizontal filter row with dropdown selects and search input
- `empty-state.tsx` — Illustration + message + CTA for empty lists
- `metric-card.tsx` — KPI card with label, value, trend indicator

## Route Structure

```
GET    /libraries                    → libraries.index
GET    /libraries/create             → libraries.create
POST   /libraries                    → libraries.store
GET    /libraries/{library}          → libraries.show
GET    /libraries/{library}/edit     → libraries.edit
PATCH  /libraries/{library}          → libraries.update
DELETE /libraries/{library}          → libraries.destroy
POST   /libraries/{library}/scan     → libraries.triggerScan
POST   /libraries/{library}/toggle-job → libraries.toggleJob

GET    /executions                   → executions.index
GET    /executions/{execution}       → executions.show
POST   /executions/{execution}/retry → executions.retry
POST   /executions/{execution}/cancel→ executions.cancel

GET    /workers                      → workers.index
GET    /workers/{worker}             → workers.show
```

## Risks / Trade-offs

- **[Scope creep]** Adding controllers and pages is a lot of files. → Mitigation: batch by entity, tasks are ordered Libraries → Executions → Workers → Dashboard
- **[Pagination without real-time]** Queue depth and execution status are live data but pages are static. → Mitigation: start with page refresh, add polling in a later change. Document this as known limitation
- **[Form validation duplication]** Backend Form Requests + frontend form state. → Mitigation: let Laravel handle validation, Inertia propagates errors to the form component. Don't duplicate rules in JS
