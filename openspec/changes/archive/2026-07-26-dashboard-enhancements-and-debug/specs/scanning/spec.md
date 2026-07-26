## MODIFIED Requirements

### Requirement: Libraries in PENDING_SCAN Are Picked Up
The `dueForScan` scope SHALL reliably pick up all libraries with status `PENDING_SCAN` that have enabled jobs.

#### Scenario: Library with PENDING_SCAN and no jobs
- **WHEN** a library has status `PENDING_SCAN` but no `libraryJobs` records
- **THEN** it SHALL be excluded from `dueForScan` (unchanged)
- **THEN** the logs SHALL indicate why the library was skipped

#### Scenario: Library with PENDING_SCAN and jobs
- **WHEN** a library has status `PENDING_SCAN` and at least one `libraryJob`
- **THEN** it SHALL be picked up by `dueForScan` regardless of `last_scan`
- **THEN** `scan:libraries` SHALL process it on the next tick
