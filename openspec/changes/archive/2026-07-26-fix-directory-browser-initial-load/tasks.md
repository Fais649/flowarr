## 1. Fix Root Directory Pre-Loading

- [x] 1.1 Add `useEffect` to `DirectoryBrowser` that calls `loadChildren('/')` when the dialog opens and root is not yet cached
- [x] 1.2 Fix chevron arrow disappearing on expand — only hide after confirming directory is empty (not while loading)
- [x] 1.3 Fix layout jump on loading — replace multi-row skeletons with a single compact spinner icon
- [x] 1.4 Fix "Empty directory" indentation — increase padding so it clearly nests under its parent
- [x] 1.5 Verify fixes compile: `npm run types:check` — no new errors
