## 1. Directory Picker Fix

- [x] 1.1 Add visible error state to DirectoryBrowser — show HTTP status message when API returns non-200, show connection error on network failure
- [x] 1.2 Default `children` to empty array when key missing — prevent TypeError on leaf nodes
- [x] 1.3 Debug and fix the underlying cause if the API is returning 500/redirect (check session, DB, route cache)
- [x] 1.4 Verify fix works end-to-end: Browse button → modal opens → tree renders

## 2. CI Completion Gate Rule

- [x] 2.1 Document CI completion gate in OpenSpec config or procedure artifact
- [x] 2.2 Update the completion checklist in OpenSpec with explicit CI step
