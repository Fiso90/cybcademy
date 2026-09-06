# CybCademy — Data Retention & Deletion Policy

**Status:** Draft — legal review recommended, but grounded in the platform's actual, built retention behaviour (not aspirational) per the Database Design Document.

---

## 1. Purpose

Defines how long CybCademy retains different categories of data, and what happens to it on deletion — both during normal operation and upon a Customer's subscription ending.

## 2. Retention Schedule by Data Category

| Data Category | Default Retention | Rationale | Configurable? |
|---|---|---|---|
| Active employee records | Duration of employment + [PLACEHOLDER: X years post-deactivation] | Compliance evidence value persists after someone leaves | Yes, per-tenant (Database Design Document, `tenants.compliance_frameworks` supports this conceptually; **actual per-tenant retention configuration UI/enforcement is not yet built** — flagged as a real gap here) |
| Training completion records, certificates | Same as employee record | Audit evidence | Same |
| **Policy acknowledgement records** | **Permanent — cannot be deleted or edited** | By design (BR-3): these are evidentiary records whose value depends on immutability; a "deletion" is functionally impossible without undermining their purpose | **No** — this is an intentional platform constraint, not a configuration gap |
| **Audit logs** | Currently unbounded (no automated purge) | Compliance/security evidence | **This is a real, unresolved gap** — the Database Design Document explicitly flags `audit_logs` as an unbounded, ever-growing table with no archival policy; a genuine data retention policy needs a real answer here (e.g., "retained for 7 years, then archived to cold storage, then deleted"), not "retained forever by default," which is neither a defensible retention decision nor good for the storage-growth concerns flagged since Phase 5 |
| Phishing simulation results | [PLACEHOLDER — recommend a defined period, e.g., 2 years, rather than indefinite, given their sensitivity] | Training effectiveness trending | Not currently configurable |
| Human Risk Score history | Same open gap as audit_logs — unbounded time-series table | Trend analysis | Not currently configurable |
| Incident reports | [PLACEHOLDER — likely needs to follow Customer's own incident-retention policy, which varies by industry] | | |
| Account credentials (password hashes, MFA secrets) | Duration of active account | Not needed post-deactivation | N/A |

## 3. Deletion Upon Subscription Termination

1. Customer has [PLACEHOLDER: e.g., 30 days] post-termination to export data via the Audit Centre's export functionality.
2. After this window, Customer Data is deleted from primary storage.
3. Backups (per the Security Architecture Document's backup process) are retained per the standard backup rotation ([PLACEHOLDER: e.g., 30 days] per `deploy/backup.sh`'s current retention setting) and are then purged, meaning a small residual copy of deleted data exists in backup storage for that window — this should be disclosed to Customer, not treated as a technicality.

## 4. Deletion Upon Individual Employee Deactivation

Deactivating an employee (Administrator Guide, Section 2) does **not** delete their historical records — this is deliberate, since those records remain compliance evidence for the organisation. If Customer requires actual deletion of a specific individual's data (e.g., responding to a data subject deletion request under POPIA/NDPA), this currently requires [PLACEHOLDER: manual intervention — **there is no self-service "delete this employee's data entirely" function in the current build**, which is a real gap for fulfilling erasure requests and should be prioritised, especially given the compliance-focused positioning of this product].

## 5. The Immutability Tension, Stated Plainly

Several of this platform's core design decisions (policy acknowledgement immutability, audit log append-only design) exist specifically *because* deletability would undermine their evidentiary value — but data protection law generally expects a "right to erasure" to exist somewhere. This is not a contradiction that can be resolved by policy language alone; it needs a genuine legal and product decision about which categories of data are exempt from erasure requests (as evidentiary/legal-obligation records, which most data protection frameworks do carve out) versus which categories must support real deletion. **This document flags the tension; it does not resolve it — that resolution needs legal input specific to each applicable jurisdiction.**

## 6. Backup Retention

Per `deploy/backup.sh`: [PLACEHOLDER: confirm actual configured retention — the script's default is 30 days for local pruning, but the object-storage copy's lifecycle policy is a separate, not-yet-documented configuration].

---

**Recommended next steps before this is a real, usable policy:**
1. Resolve Section 5's tension with counsel — decide which tables are legitimately erasure-exempt and document why, per jurisdiction.
2. Build the actual per-tenant retention configuration and individual-deletion tooling flagged as missing in Sections 2 and 4 — this document currently describes a policy the platform can't yet fully enforce.
3. Set a real number for `audit_logs` and `human_risk_scores` retention/archival — "unbounded" is not a retention policy.
