## Context

The directory picker (DirectoryBrowser component) fetches a directory tree from `/libraries/directories` and renders it in a modal. The endpoint is behind `auth` + `verified` middleware and uses the database session driver. If any link in this chain fails — expired session, DB connectivity, filesystem permission — the fetch `catch` block silently sets an empty tree, showing "No directories found" with no error feedback. The component also has edge cases around undefined `children` keys for leaf nodes and no visible error state.

Separately, the GitHub Actions workflow (`publish.yml`) keeps failing across commits because task completion is checked manually without a formal gate requiring a green CI run.

## Goals / Non-Goals

**Goals:**
- Fix the directory browser so the tree renders on first open
- Add visible error feedback when the API call fails
- Document and fix the backend cause if it's a code-level issue
- Formalize CI completion gate as an OpenSpec enforcement rule

**Non-Goals:**
- Rewrite the directory browser or replace the backend implementation
- Change the authentication or session driver
- Modify the GitHub Actions workflow itself (only the completion gate rule)

## Decisions

### Directory picker: add visible error state
The current catch block hides all failures. Replace it with a three-state display: loading → tree / no-directories / error. The error state shows the HTTP status or error message so the user (and support) can distinguish "no media mounted" from "session expired" from "server error."

### Directory picker: default `children` to `[]`
The backend omits the `children` key when `$depth` reaches 0. This can cause a TypeError on deeply nested directory expansion. Fix: default `children` to `[]` when destructuring in DirectoryNode.

### CI completion gate: OpenSpec doctrine
Add a `completion-rules` section to the OpenSpec config (or a procedure artifact) that agents must check before marking tasks done. The rule is: "Run CI locally, confirm green, commit, push, verify remote green." This is a document-level enforcement (agent reads it) not a scripted hook.

## Risks / Trade-offs

- [Backend 500] → Mitigation: if the root cause is a DB config or session issue on the Docker image, fixing the frontend error display won't fix the tree. But it will surface the real problem.
- [Over-engineered error states] → Mitigation: keep it simple — one error state with message text, no retry button unless needed.
- [CI rule unenforceable] → Mitigation: agents can't be forced to follow a rule, but documenting it in the OpenSpec config makes the instruction explicit and auditable.
