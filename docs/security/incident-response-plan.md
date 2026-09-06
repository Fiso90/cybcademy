# CybCademy — Incident Response Plan

**Audience:** Solunar Informatics internal engineering/security team. **Not** the in-app "Report Phishing" employee flow (that's a *feature* of the product); this is Solunar's own plan for when something goes wrong with the platform itself — a security incident affecting Customer Data, an outage, or a breach.

**Status:** Draft — this is the first version of this document; it has never been exercised via a tabletop drill.

---

## 1. Severity Classification

| Severity | Definition | Examples |
|---|---|---|
| **SEV-1 — Critical** | Confirmed or strongly suspected unauthorised access to Customer Data, or a complete platform outage | Evidence of cross-tenant data access; database credentials compromised; RLS bypass discovered; platform fully down |
| **SEV-2 — High** | Significant security weakness discovered with no evidence of exploitation, or major feature outage | A dependency vulnerability with a working exploit exists in production; queue workers down, blocking phishing sends or audit exports |
| **SEV-3 — Medium** | Contained issue, workaround available | A single tenant's export job stuck; a non-critical feature degraded |
| **SEV-4 — Low** | Cosmetic or minor | UI bug, non-security-relevant |

## 2. Immediate Response (First 60 Minutes) — SEV-1/SEV-2

1. **Declare the incident.** Whoever discovers it (engineer, automated alert, or a customer report) notifies [PLACEHOLDER: on-call rotation / escalation contact — no formal on-call process exists yet, per the gap flagged in the Maintenance Guide and Phase 15's Go-Live Checklist "support process defined... not addressed"].
2. **Contain, don't yet fix.** For a suspected cross-tenant data access issue specifically: this project's own testing (`TenantIsolationTest`, `CrossTenantHttpAccessTest`) is the first thing to re-run against the affected environment — if either fails where it previously passed, that's strong evidence of what broke and roughly when, since these tests exist precisely to catch this failure mode.
3. **Assess scope.** Which tenant(s) are affected? Check `audit_logs` for the relevant time window and resource — this is exactly the kind of question the audit log system (Epic E1/E7) was built to answer quickly.
4. **Preserve evidence before remediating** where feasible — a rollback (`deploy/rollback.sh`) or a hotfix should not destroy the audit trail of what happened, since that trail is needed for both the post-incident review and, for a SEV-1 confirmed breach, regulatory notification.

## 3. Containment and Eradication

- If the incident stems from a code defect: use `deploy/rollback.sh` to revert to the last known-good deployment (see the Deployment Guide) rather than attempting a live hotfix under pressure, unless rollback would leave the vulnerability equally exposed.
- If the incident involves compromised credentials (database, API keys, `ANTHROPIC_API_KEY`, etc.): rotate immediately. [PLACEHOLDER: credential rotation runbook — not yet written; this is a real gap, since "rotate the database password" touches multiple running containers and the process for doing so without extended downtime hasn't been documented anywhere in this project]
- If the incident involves a specific tenant's data being exposed to another tenant: that tenant's Administrator must be notified per the notification timeline below, and the specific query/code path involved must be identified precisely enough to confirm the scope (which other tenants, if any, were also affected) — do not guess at scope, verify it against `audit_logs`.

## 4. Customer Notification

- **For a confirmed SEV-1 involving Customer Data:** notify affected Customer's Administrator(s) directly. [PLACEHOLDER: notification timeframe — must be set to satisfy the DPA's breach notification clause (itself a placeholder pending legal review) and Customer's own regulatory deadlines under POPIA/NDPA, which the DPA needs to accommodate]
- Notification should include: what happened (to the extent known), what data was affected, what Solunar has done/is doing, and what Customer should consider doing on its side.
- **Do not over-promise specifics before they're confirmed** — an initial notification saying "we are investigating a potential issue affecting your organisation's data and will update you within [X hours] with more detail" is more honest and more defensible than a rushed, wrong initial assessment.

## 5. Post-Incident Review

Within [PLACEHOLDER: e.g., 5 business days] of resolution, for any SEV-1 or SEV-2:
1. Written timeline of what happened, when detected, when contained, when resolved.
2. Root cause — genuinely identified, not just "patched the symptom." If the root cause traces to a gap already named somewhere in this project's own documentation (e.g., the missing malware scanning, the unfinished notification delivery, an untested RLS edge case), the review should say so explicitly rather than treating it as a novel surprise.
3. Specific remediation items with owners and dates, added to the Maintenance Guide's recurring task list if they represent an ongoing risk rather than a one-time fix.
4. Whether this incident should have been caught earlier by existing tests (`TenantIsolationTest` and its siblings exist specifically to catch tenant-isolation regressions) — if it wasn't, that's a test-coverage gap worth its own follow-up.

## 6. What This Plan Does Not Yet Cover

Being honest about its own gaps, consistent with how every other document in this project has been written:

- **No on-call rotation or paging system exists.** This plan assumes someone notices and responds, but there's no formal mechanism ensuring that happens outside business hours.
- **No credential rotation runbook exists** (Section 3).
- **This plan has never been exercised.** A tabletop exercise — walking through a simulated SEV-1 with the actual team who would respond — would surface gaps in this document faster than continuing to refine it on paper. Recommend scheduling one before this is relied upon in a real incident.
- **Regulatory notification specifics** (which regulator, what timeframe, what form) depend on the DPA and Privacy Policy's placeholder sections being completed with real legal input first.
