## Context

The library create/edit form uses a plain text `<Input>` for `base_path` with no filesystem feedback. The app is Dockerized, so the user needs to enter paths that exist inside the container (bind-mounted volumes). Currently there's no way to browse the container filesystem from the UI, and no validation that the entered path actually exists.

## Goals / Non-Goals

**Goals:**
- Server endpoint to list directories at a given path
- Directory tree dialog on the library create/edit page
- Text input + Browse button pattern (power users can still type)
- Server-side validation that `base_path` is a real, readable directory
- Path traversal protection on the listing endpoint

**Non-Goals:**
- File browser (files are not listed, only directories)
- Upload or file management
- SMB/network mount browsing (only local container filesystem)
- Recursive full-tree loading (lazy load on expand only)

## Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Endpoint URL | `GET /libraries/directories?path=<encoded>` | Lives under `/libraries` since it's a library-creation concern. Simple query param. JSON response. |
| Response format | `{ path: string, directories: [{ name: string, path: string }] }` | Minimal. Frontend just needs name + full path for each child. Current path is echoed for context. |
| Directory listing backend | PHP `DirectoryIterator` with `isDir()` filter, skip hidden (dot-prefixed) | No external dependencies. Already available in the PHP runtime. |
| Path traversal mitigation | Reject paths containing `..` or starting with `~` | Simple string check before any filesystem access. Defense in depth. |
| Tree UI component | Custom `DirectoryBrowser` React component using shadcn Dialog + collapsible tree | No existing tree component in shadcn/ui. Build a lightweight one with recursive rendering and lazy loading. |
| Validation rule | Custom Laravel rule or closure: `new Rule(fn ($attr, $value, $fail) => is_dir($value) && is_readable($value) ? null : $fail(...))` | Simple inline check. No need for a dedicated rule class for this scope. |
| Expand behavior | Click chevron → fetch children → render. Cache in component state during session. | Avoids repeated server calls for the same directory within one dialog session. |

## Risks / Trade-offs

- **Path traversal** — The directory listing endpoint exposes filesystem structure. Mitigation: reject `..`, `~`, and null bytes. Only list directories, never read file contents. Auth-gate the endpoint.
- **Large directories** — Directories with thousands of entries could be slow. Mitigation: `DirectoryIterator` is lazy (not loaded into memory all at once). Acceptable since this is a self-hosted tool — users won't browse `/usr`.
- **Hidden directories** — We skip dot-prefixed dirs. This hides `.stfolder`, `.cache`, etc. which is the right default. Users can still type the path manually if needed.
- **Permission errors** — Some directories may be unreadable by www-data. Mitigation: `DirectoryIterator` will skip those silently (or return empty). The submit validation will catch if the chosen path is unreadable.
