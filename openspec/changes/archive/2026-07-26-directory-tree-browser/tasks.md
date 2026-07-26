## 1. Backend — Directory Listing Endpoint

- [x] 1.1 Create `App\Http\Controllers\DirectoryController` with a `__invoke` method that accepts a `path` query parameter
- [x] 1.2 Add path traversal validation — reject paths containing `..` or starting with `~`
- [x] 1.3 Implement directory listing using `DirectoryIterator` — return directory name and full path for each child
- [x] 1.4 Add route `GET /libraries/directories` pointing to the controller
- [x] 1.5 Create a feature test for the directory listing endpoint (valid root, valid subdir, non-existent path, path traversal attempt)

## 2. Backend — Filesystem Validation on Library Requests

- [x] 2.1 Add a `exists_and_readable` validation rule to `StoreLibraryRequest` — check `is_dir()` and `is_readable()` on `base_path`
- [x] 2.2 Add the same validation rule to `UpdateLibraryRequest`
- [x] 2.3 Verify error messages are user-friendly ("The selected directory does not exist or is not readable")

## 3. Frontend — Directory Browser Component

- [x] 3.1 Create `resources/js/components/directory-browser.tsx` — a React component wrapping a shadcn Dialog
- [x] 3.2 Implement the directory tree with lazy loading — on expand, fetch `GET /libraries/directories?path=<parent>` and render children
- [x] 3.3 Add loading states (skeleton/spinner) while child directories are being fetched
- [x] 3.4 Add empty and error states (no subdirectories, failed to load)
- [x] 3.5 Handle directory selection — click to highlight, "Select" button or double-click to confirm
- [x] 3.6 Return selected path via `onSelect` callback prop

## 4. Frontend — Integrate into Library Create/Edit Page

- [x] 4.1 Add a "Browse" button next to the `base_path` input field in `libraries/create.tsx`
- [x] 4.2 Wire the Browse button to open the DirectoryBrowser dialog
- [x] 4.3 When a directory is selected from the browser, update the form data's `base_path` field
- [x] 4.4 Run `vendor/bin/sail artisan test --compact --filter=Libraries` to confirm existing tests still pass
- [x] 4.5 Run `npm run types:check` to confirm no TypeScript errors
- [x] 4.6 Run `vendor/bin/sail bin pint` to format PHP code
