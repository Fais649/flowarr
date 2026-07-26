## ADDED Requirements

### Requirement: Automated Re-scan Cycle
The system SHALL automatically re-scan libraries on their configured `scan_interval` without requiring manual intervention.

#### Scenario: Library re-scans after interval
- **WHEN** a library has status `pending` and `(last_scan + scan_interval) < now()`
- **THEN** the `scan:libraries` command SHALL pick it up for scanning
- **THEN** status SHALL be set to `scanning` during scan
- **THEN** after scan, status SHALL be restored to `pending` and `last_scan` updated

#### Scenario: First scan on creation
- **WHEN** a library is created with status `pending_scan` and no `last_scan`
- **THEN** the next `scan:libraries` run SHALL pick it up immediately

### Requirement: Manual Scan Trigger
Users SHALL be able to trigger an immediate scan via the UI.

#### Scenario: Trigger scan button
- **WHEN** user clicks "Scan Now" on a library
- **THEN** the library status SHALL be set to `pending_scan`
- **THEN** the next `scan:libraries` run SHALL scan it regardless of interval

## MODIFIED Requirements

<!-- No existing requirements change. The scheduled scan requirement in the original spec ("query all libraries where status is not paused/stopped and (last_scan + scan_interval) < now()") already describes the correct behavior — the fix is implementation alignment. -->
