# CybCademy — Phase 10 Code Drop: Epic E7 — Audit Centre

Sixth slice of Phase 10 (Sprint 13 per the Phase 9 plan). Builds directly on `audit_logs` and `App\Support\AuditLogger`, both in place since Epic E1 — nothing here creates a new way to write audit data, only new ways to read and export it.

## What's included

| Layer | Files |
|---|---|
| **Migration** | `audit_exports` — tracks async export job status, distinct from `audit_logs` itself |
| **Model** | `AuditExport` |
| **Services** | `AuditLogQueryService` (read-only, query-builder based — no writable Eloquent model exists for `audit_logs`, deliberately), `AuditExportService` (initiates the async job) |
| **Job** | `GenerateAuditExportPackage` — runs on its own dedicated queue, targets the FR-8.2 5-minute SLA |
| **Controller** | `AuditController` — implements the `POST /audit/export` + `GET /audit/export/{jobId}` async pattern from Phase 8 |
| **Tests** | `AuditExportTest` — covers both the request-time log entry and the completion-time log entry |

## Design decisions worth knowing about

1. **There is still no writable Eloquent model for `audit_logs`.** `AuditLogQueryService` reads via the query builder, exactly like `AuditLogger` writes via the query builder — this consistency is deliberate, so there's never a tempting `AuditLog::find($id)->update(...)` available anywhere in the IDE's autocomplete for a future developer to reach for.
2. **The export request itself is logged, separately from the export's completion.** If `GenerateAuditExportPackage` fails partway through, there's still a permanent record that someone requested a bulk data pull at a given time — which matters for security monitoring (Phase 7 Section 2, A09) regardless of whether the export technically succeeded.
3. **Export generation has its own dedicated queue**, isolated from Epic E4's `phishing-sends` queue. These have very different urgency profiles — a compliance officer waiting on a 5-minute SLA should never be stuck behind a backlog of phishing simulation emails, and vice versa.
4. **The job's timeout is set to exactly the SLA (300 seconds).** A job that would exceed 5 minutes fails loudly (and is retried once) rather than silently taking 8 minutes and quietly missing FR-8.2 — the constraint from Phase 3's Risk Assessment ("time-to-generate-audit-evidence" as a named KPI) is enforced in code, not just aspirational.
5. **Download links are signed and expire in 15 minutes.** The completed export file is never publicly addressable — `exportStatus()` only returns a `download_url` once the export is `completed`, and that URL itself expires, consistent with Phase 4 Section 10's "no public bucket access" rule and Phase 7's least-disclosure principle applied to bulk data.

## Known simplifications, called out honestly

- Only CSV export is implemented in this excerpt; PDF export (also specified in FR-8.2) follows the same query result through a PDF-templating step — the query and job structure already support it, but the actual PDF rendering library/approach is a Phase 13 tooling decision, not duplicated here.
- `AuditLogQueryService`'s action-prefix filter (`'action', 'like', $filters['action'] . '%'`) is a simple prefix match (e.g. filtering `"policy."` catches `policy.acknowledged`, `policy.published`, etc.) — sufficient for v1.0's audit action taxonomy, but worth revisiting if the action namespace grows complex enough to need proper faceted filtering.

## Next steps

Per the Phase 9 sprint plan, next is **Epic E8 (API & Integrations)** — though most of the actual endpoint work has already landed module-by-module across E1–E7; E8's remaining scope is primarily formalising the OpenAPI spec as a generated artifact and any integration-specific concerns (webhooks, rate-limit tiers) not yet covered. After that, **Epic E9 (AI Features)** is the largest remaining piece of net-new functionality. Let me know how you'd like to sequence those two.
