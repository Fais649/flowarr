## Why

The directory browser dialog shows "Empty directory" on first open because the root path `/` is expanded by default but its contents are never fetched — the fetch only fires on arrow click. Collapsing and re-expanding loads the data, but the initial experience is broken.

## What Changes

- Load root directory contents immediately when the dialog opens, rather than waiting for the user to click the expand arrow
- The root node starts expanded AND populated on first render

## Capabilities

### New Capabilities

*(none)*

### Modified Capabilities

- `directory-picker`: The "Navigate tree" scenario changes — the root directory SHALL be pre-loaded when the dialog opens, not only on expand click

## Impact

- `resources/js/components/directory-browser.tsx` — add `useEffect` to fetch root directory on dialog open
