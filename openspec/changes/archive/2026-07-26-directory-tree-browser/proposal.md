## Why

The library create/edit form requires users to type a full absolute path by hand (`/path/to/media`) with no feedback on whether the directory exists or even what's available on the container filesystem. For a Dockerized app where media volumes are bind-mounted into the container, this means guessing mount paths and typing blindly — a poor UX that will frustrate users on initial setup.

## What Changes

- Add a server-side directory listing endpoint that returns child directories for a given path
- Replace the plain text input with a text input + "Browse" button pattern
- Browse button opens a directory tree dialog that lazily loads subdirectories from the server
- Selecting a directory in the tree fills the input with the chosen path
- Add server-side validation on store/update that the `base_path` is a real, readable directory on disk

## Capabilities

### New Capabilities

- `directory-picker`: Server-side directory tree browser for selecting library base paths within the container filesystem

### Modified Capabilities

- `library-management`: Library base_path validation now checks directory exists on disk; form UI includes directory browser dialog

## Impact

- `routes/web.php` — add directory tree API endpoint
- `app/Http/Controllers/DirectoryController.php` — new controller for directory listing
- `app/Http/Requests/StoreLibraryRequest.php` — add `base_path` filesystem validation
- `app/Http/Requests/UpdateLibraryRequest.php` — add `base_path` filesystem validation
- `resources/js/pages/libraries/create.tsx` — add Browse button + directory dialog
- `resources/js/components/directory-browser.tsx` — new directory tree component
- `resources/js/components/ui/` — may add tree/list primitives if needed
