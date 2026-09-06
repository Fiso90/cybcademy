# CybCademy — Maintenance Guide

## Recurring Operational Tasks

| Task | Frequency | Owner | Reference |
|---|---|---|---|
| Database backup restore drill | Quarterly | Named individual, not "the team" | `deploy/README.md` |
| Independent security assessment (OWASP ASVS Level 2) | Before GA, then annually | Solunar Informatics + external assessor | Flagged since Phase 3 Section 17; still not scheduled as of Phase 13 |
| Dependency vulnerability scan review | Weekly (automated via Dependabot/Composer audit), human review monthly | Engineering | Security Architecture document, Section 2 (A06) |
| Failed queue job review | Weekly | Engineering | `queue:prune-failed` runs automatically (see `app/Console/Kernel.php`), but review *why* jobs failed before they're pruned |
| Certificate/PDF export storage growth review | Monthly | Engineering | No lifecycle/archival policy is currently configured on the certificates or audit-exports storage prefixes |
| `human_risk_scores` and `audit_logs` table size review | Monthly, until partitioning is implemented | Engineering | Flagged as a risk since the Database Design document — these are unbounded, append-only tables |

## Scheduled Jobs (automated, verify they're actually running)

Configured in `app/Console/Kernel.php`, driven by the `scheduler` container in `docker-compose.yml`:

- **Human Risk Score recalculation** — nightly at 02:00, per tenant
- **Policy acknowledgement reminders** — weekly, Mondays at 09:00, per tenant
- **Failed job pruning** — daily

If dashboards ever show stale Human Risk Scores, check the `scheduler` container's logs first — a crashed or stopped scheduler container is a silent failure mode (no user-facing error, scores simply stop updating).

## Dependency Updates

- PHP/Composer packages: Dependabot PRs, reviewed against the PSR-12/PSR-4 conventions before merge
- The `ANTHROPIC_MODEL` environment variable is intentionally configurable without a code deploy — check periodically whether a newer Claude model is available and worth adopting, rather than assuming the pinned default stays current indefinitely

## Database Maintenance

- **RLS policy coverage** is checked automatically by `tests/Security/TenantIsolationTest.php`'s `test_every_tenant_scoped_table_has_an_rls_policy` — this runs in CI on every PR, but if a table is ever added outside the normal migration flow (e.g. a manual `ALTER TABLE`), verify RLS coverage manually against `pg_policies`.
- **`audit_logs` privilege revocation** (no UPDATE/DELETE for the application role) should be re-verified after any database user/role changes, since a careless `GRANT ALL` during troubleshooting could silently undo this control.

## Capacity Planning Signals

Watch these specifically, since they're the growth patterns flagged as risks throughout the design phases:

- Row count growth on `human_risk_scores` and `audit_logs` (time-series, unbounded — partitioning was flagged as a pre-emptive recommendation, not yet implemented)
- Queue depth on `audit-exports` specifically — a sustained backlog here means the FR-8.2 5-minute SLA is at risk, and it has the tightest job timeout (300s) of any queue in the system
- Redis memory usage — a single Redis instance backs sessions, cache, and all three queues (a deliberate v1.0 simplification flagged as a scaling risk to revisit)
