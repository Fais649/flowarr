## Why

The directory browser has at least 9 distinct bugs, all caused by the per-node lazy-loading architecture:

1. **Freeze on expand** — Each `setCache({...prev})` replaces the entire cache object, triggering a full re-render cascade through every descendant `DirectoryNode`. Combined with per-expand async fetches, this stutters the UI.

2. **Chevron disappearing** — `isExpanded` flips to `true` before the loading state commits, causing `confirmedEmpty` to briefly report empty.

3. **Stuck "loading" state** — Expand handler guards with `!state`: if state is `'loading'` (from a previous attempt), `!state` is `false`, so `loadChildren` is never called again. Collapsing and re-expanding a loading directory does nothing — it stays stuck.

4. **Stuck "error" state** — Same guard: `!state` is `false` for `'error'` too. Failed directories can never be retried within the same dialog session.

5. **Stale cache between sessions** — `cache` state never resets on dialog close. An errored path from a previous dialog open still shows the error next time.

6. **Stale `expandedPaths` between sessions** — `expandedPaths` persists across opens. Previously expanded directories appear expanded on re-open even though their children haven't been fetched yet.

7. **Stale `'loading'` after abort** — Closing the dialog mid-fetch aborts the request, but the catch handler returns without clearing the `'loading'` state. The root path stays `'loading'` in cache until the dialog re-opens.

8. **Full-tree cascade re-renders** — `cache`, `expandedPaths`, and `selectedPath` are all passed as props to every `DirectoryNode`. Any state change re-renders the entire tree from root down.

9. **`getRealPath()` masks original paths** — The backend resolves symlinks via `getRealPath()`. `/bin` becomes `/usr/bin`. The displayed path doesn't match what the user navigated to.

All nine bugs share one root cause: async per-node fetch + recursive shared-state architecture.

## What Changes

- Replace the per-node lazy-loading architecture with a single bulk-fetch approach
- Backend: Add a `?depth=N` query parameter to the directory listing endpoint that returns nested directories up to N levels deep
- Frontend: When the dialog opens, fetch the directory tree in one call with `depth=5` — enough for any practical library path
- Cache the entire tree in a single `useState`; render synchronously — no per-node loading states, no AbortControllers, no async edge cases
- Remove the `loadChildren`/`toggleExpand` async pattern entirely from `DirectoryNode`

## Capabilities

### New Capabilities

*(none)*

### Modified Capabilities

- `directory-picker`: The directory browser SHALL pre-fetch a multi-level directory tree in one request instead of lazy-loading per node. The "Navigate tree" scenario changes from async-per-expand to synchronous rendering from cached tree data.

## Impact

- `app/Http/Controllers/DirectoryController.php` — add optional `depth` parameter for recursive listing
- `resources/js/components/directory-browser.tsx` — complete rewrite of state management: single fetch on open, synchronous tree rendering, no per-node loading
- `openspec/specs/directory-picker/spec.md` — update Navigate tree scenario
