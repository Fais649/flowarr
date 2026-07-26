## Context

The `DirectoryBrowser` component uses a recursive `DirectoryNode` pattern where each expand triggers an individual fetch call (`loadChildren(path)`). The state management has 9 distinct bugs including UI freeze, stuck loading/error states, stale cross-session state, and chevron flickering — all from the same root cause: async per-node fetch + recursive shared-state architecture.

## Goals / Non-Goals

**Goals:**
- Eliminate per-node async fetches — single bulk request when dialog opens
- All directory data rendered synchronously — no loading/error states per node
- Default depth of 5 levels (covers `media/movies/action/2024/...` patterns)
- Remove AbortController, per-node loading state, and `expandedPaths` complexity

**Non-Goals:**
- Recursing deeper than 5 levels — deeper dirs are rare for library paths
- Real-time filesystem watching — dialog fetches fresh each open
- File browser (still directories only)
- Pagination or virtual scrolling

## Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Backend recursion | Add `depth` param to existing endpoint: `/libraries/directories?path=/&depth=5` | Uses existing route. Optional param — defaults to 0 (no recursion, backward-compatible). |
| Return format | Nested `children` array: `{name, path, children[]}` | Matches tree structure directly. Frontend renders without transformation. |
| Max depth | 5 | Practical limit. `/media/videos/movies/action/2024/releases/` is 6 segments — depth 5 covers root + 5 levels = 6 segments total. |
| Frontend state | Single `useState<DirectoryEntry[]>` for root children | No more `Cache` type, no `'loading' | 'error'` states. Flat data = simple rendering. |
| Tree rendering | Recursive component, but data is already nested | Same recursive rendering, but NO async per node. Chevron visibility is trivial: `children.length === 0`. |
| Dialog open | `useEffect` fetches tree, shows spinner until loaded | One loading state for the whole dialog, not per node. Single skeleton while fetching. |

## Risks / Trade-offs

- **[Risk] Deep filesystems** — Some mounts could have >5 levels of nested dirs. Mitigation: depth 5 covers the vast majority of media library layouts. Users can still type a path manually for deeper structures.
- **[Risk] Large directories** — `/usr/share` with hundreds of subdirs could produce a large response. Mitigation: `DirectoryIterator` is lazy; nested response is built server-side but capped at 5 levels. Acceptable for self-hosted tool.
- **[Trade-off] Stale data during session** — Tree is fetched once when dialog opens. Filesystem changes during the session aren't reflected. Acceptable — user can close and re-open the dialog.
