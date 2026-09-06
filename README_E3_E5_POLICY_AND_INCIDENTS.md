# CybCademy — Phase 10 Code Drop: Epics E3 (Policy Management) + E5 (Incident Reporting)

Third slice of Phase 10, per the Phase 9 sprint plan (Sprints 7 and 10, paired here since both are smaller in scope than E1/E2 and don't depend on each other).

## What's included

### Epic E3 — Policy Management
| Layer | Files |
|---|---|
| Migrations | `policies`, `policy_acknowledgements` |
| Models | `Policy`, `PolicyAcknowledgement` (`$timestamps = false` — no `updated_at`, ever) |
| Services | `PolicyService` (versioning/publishing, outstanding-acknowledgement query), `PolicyAcknowledgementService` (the one write path into the immutable table) |
| Controller | `PolicyController` |
| Tests | `PolicyAcknowledgementImmutabilityTest` |

### Epic E5 — Incident Reporting
| Layer | Files |
|---|---|
| Migrations | `incidents` |
| Models | `Incident` |
| Services | `IncidentService` (submission + triage with an explicit status state machine) |
| Controller | `IncidentController` |
| Tests | `IncidentStatusTransitionTest` |

## Design decisions worth knowing about

1. **BR-3 immutability is enforced at three levels, not one.** The migration's unique constraint on `(tenant_id, policy_id, user_id)` makes a second acknowledgement of the *same version* impossible at the database level; `PolicyAcknowledgementService` catches that constraint violation and surfaces it as a clear validation error rather than silently succeeding or overwriting; and the model has `$timestamps = false` so there's no `updated_at` column inviting an edit in the first place. A "correction" is structurally forced to be a new policy version with its own fresh acknowledgement row — tested explicitly in `test_correcting_an_acknowledgement_requires_a_new_policy_version_not_an_edit`.
2. **Publishing a new policy version does not retroactively require re-acknowledgement bookkeeping tricks.** Because acknowledgements are keyed to a specific version, publishing v2 automatically means everyone has an "outstanding acknowledgement" for v2, without any explicit reset logic needed — the absence of a v2 row *is* the outstanding state.
3. **Incident status changes go through an explicit state machine**, not an arbitrary status update. `open → closed` directly is rejected; the incident must pass through `in_progress` (or `resolved`) first, preserving a meaningful triage trail. `resolved → in_progress` is deliberately allowed (reopening a disputed resolution), while `closed` is a true terminal state.
4. **Incident reporting has no permission gate** (`StoreIncidentRequest::authorize()` returns `true` unconditionally) — this is deliberate, not an oversight, matching the Phase 6 design principle that the "Report Phishing" action must always be available to any employee, never hidden behind a permission check that could delay someone in a real suspicious-email moment.

## Known simplifications, called out honestly

- Policy file uploads use a `private` disk in this excerpt; wiring that disk to the actual S3-compatible/R2 backend (per Phase 4 Section 10) is a `config/filesystems.php` configuration detail for Phase 13, not something this module code needs to know about directly.
- `PolicyService::usersWithOutstandingAcknowledgement()` is a pure query method with no scheduled dispatch yet — the actual reminder cadence/scheduling (FR-4.3) belongs to the Notifications module (Epic E10) consuming this method, not implemented here.

## Next steps

Per the Phase 9 sprint plan, next is **Epic E4 (Phishing Simulation)** — the module with the most Phase 7/BR-4-related sensitivity (results aggregation, non-punitive design), so worth its own focused drop rather than pairing it with something else.
