## Context

The `DirectoryBrowser` component expands the root `/` node by default (`expandedPaths` starts with `/` in state) but never fetches its children until the user clicks the expand arrow. The expand toggle only calls `loadChildren` when `!isExpanded && !state` (i.e., the node is being opened for the first time). Since the node starts expanded, the fetch never fires — it renders "Empty directory" instead.

## Goals / Non-Goals

**Goals:**
- Root directory contents fetch automatically when the dialog opens
- No visual regression — the root node should appear expanded with children already loaded

**Non-Goals:**
- Pre-loading subdirectories (keep lazy loading for depth > 0)
- Changing the data-fetching mechanism or caching strategy
- Any backend changes

## Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Trigger timing | `useEffect` when `open` becomes `true` | Dialog opens → fetch root. Simple, declarative, and idempotent. |
| Cache reset | Keep existing cache; only load root if not yet cached | Avoids re-fetching if dialog is reopened in the same session. |
| Approach | Single `useEffect` in `DirectoryBrowser` calling `loadChildren('/')` | Minimal change. No restructuring needed. |

## Risks / Trade-offs

- **[Risk] Stale root on re-open** — If the filesystem changes between dialog opens, the root cache may be stale. Mitigation: clearing the entire cache on dialog close would fix this, but adds complexity. Acceptable trade-off for a self-hosted tool.
