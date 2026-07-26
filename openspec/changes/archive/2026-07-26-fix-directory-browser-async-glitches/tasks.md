## 1. Backend — Recursive Directory Listing

- [x] 1.1 Modify `DirectoryController::listDirectories` to accept a `$depth` parameter — if > 0, recursively call itself on each subdirectory with `$depth - 1` and attach results as `children`
- [x] 1.2 Modify `DirectoryController::__invoke` to accept `depth` query param (default 5)
- [x] 1.3 Fix `DirectoryController::listDirectories` to return the original path (not `getRealPath()` resolved symlink) — use `$fileInfo->getPathname()` instead
- [x] 1.4 Update feature test to cover depth parameter (flat vs nested response)
- [x] 1.5 Run full test suite

## 2. Frontend — Rewrite Directory Browser

- [x] 2.1 Rewrite `DirectoryBrowser` state: replace `Cache` type + `loadChildren` + `toggleExpand` with single `useState<TreeNode[] | null>` for root children, and a `useEffect` that fetches `GET /libraries/directories?path=/&depth=5` when the dialog opens
- [x] 2.2 Rewrite `DirectoryNode`: remove `loadChildren` and `toggleExpand` props; accept pre-loaded nested `children` array; expand/collapse uses local `useState<boolean>` per node (not shared state)
- [x] 2.3 Chevron visibility: `children.length === 0` → no chevron. No edge cases — data is already loaded.
- [x] 2.4 Show a centered spinner inside the dialog while initial tree fetch is in progress (instead of per-node skeletons)
- [x] 2.5 Fix nested `<button>` hydration error — replaced inner button with `<span role="button">`
- [x] 2.6 Fix all dirs expanded by default — changed initial state to `useState(false)`, re-added root `/` node
- [x] 2.7 Verify with Storybook: `build-storybook` passes, story created
- [x] 2.8 Run `npm run types:check` — confirm no new errors
