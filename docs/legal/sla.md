# CybCademy Service Level Agreement (SLA)

**⚠️ DRAFT — commercial and legal review required before use.** Uptime target and remedy structure below are derived directly from the Non-Functional Requirements in the SRS/PRD (99.5% v1.0 target), but the remedy percentages and process are illustrative and need business sign-off before being offered to customers.

**Applies to:** Enterprise and Professional tier subscriptions. [PLACEHOLDER: confirm whether Basic tier receives a lesser or no formal SLA — common in tiered SaaS pricing]

---

## 1. Uptime Commitment

Solunar commits to **99.5% monthly uptime** for the CybCademy platform, measured as the percentage of time the Service is available, excluding Scheduled Maintenance and Excluded Events (below). This target matches the v1.0 Non-Functional Requirement established in the SRS — it has been designed for, but **has not yet been empirically load-tested** as of the Phase 12 Test Plan; Solunar should not offer a stricter commitment than this until that verification has actually happened.

## 2. Measurement

Uptime is measured via [PLACEHOLDER: specify monitoring methodology and tooling — the Architecture Document flags monitoring tool selection as deferred to Phase 13/DevOps and not yet finalised; this section cannot be meaningfully completed until that's decided].

## 3. Scheduled Maintenance

Solunar may perform scheduled maintenance with at least [PLACEHOLDER: e.g., 48 hours] advance notice to Customer administrators. Scheduled maintenance windows are excluded from uptime calculations. Solunar will make reasonable efforts to schedule maintenance outside Customer's core business hours where feasible for a global customer base.

## 4. Excluded Events

The uptime commitment does not apply to unavailability caused by:
- Force majeure events
- Issues with Customer's own network, equipment, or third-party services outside Solunar's control
- Customer's misuse of the Service or violation of the Acceptable Use terms
- Scheduled Maintenance (Section 3)
- [PLACEHOLDER: outages caused by named critical third parties — e.g., Cloudflare, the managed database provider — where standard SaaS SLAs typically either exclude these or pass through the upstream provider's own SLA terms rather than guaranteeing beyond what those providers themselves commit to]

## 5. Service Credits

| Monthly Uptime | Service Credit |
|---|---|
| < 99.5% but ≥ 99.0% | [PLACEHOLDER — e.g., 5% of monthly fees] |
| < 99.0% but ≥ 95.0% | [PLACEHOLDER — e.g., 10% of monthly fees] |
| < 95.0% | [PLACEHOLDER — e.g., 25% of monthly fees] |

Service credits are Customer's sole and exclusive remedy for failure to meet the uptime commitment, unless otherwise stated in the Terms of Service. [PLACEHOLDER: claim process and deadline — e.g., "Customer must request credit within 30 days of the affected month"]

## 6. Support Response Times

[PLACEHOLDER: this table needs an actual support process to exist first — flagged as a genuine gap in Phase 15's Go-Live Checklist ("support process defined... not addressed"). Do not publish response-time commitments before a support function and its staffing actually exist.]

| Severity | Definition | Target First Response |
|---|---|---|
| Critical | Complete Service outage, or a security incident affecting Customer Data | [PLACEHOLDER] |
| High | Major feature unusable, no workaround | [PLACEHOLDER] |
| Medium | Feature degraded, workaround available | [PLACEHOLDER] |
| Low | Cosmetic issue, question, feature request | [PLACEHOLDER] |

## 7. Performance Targets (Informational, Not a Guaranteed SLA)

The following are internal engineering targets from the SRS/PRD Non-Functional Requirements, listed here for transparency but **not offered as a contractual guarantee** until they have been empirically verified (per the Phase 12 Test Plan's explicit finding that none of these have been measured under real load):

- Dashboard page load: p95 < 2 seconds
- API response time: p95 < 500ms (non-report endpoints)
- Audit evidence export: < 5 minutes for tenants up to 5,000 employees

**Recommendation: do not convert these from "design target" to "contractual commitment" in any customer-facing SLA until the load-testing work flagged in Phase 12 has actually been done.** Committing to an unverified number is a real business risk, not a formality.

## 8. Data Backup and Recovery Objectives

- Recovery Point Objective (RPO): ≤ 24 hours
- Recovery Time Objective (RTO): ≤ 4 hours

Consistent with the Security Architecture Document's stated targets. **Note:** as of the Phase 15 Release document, the backup process has never been through a restore drill — these RPO/RTO figures reflect design intent, not a demonstrated capability. Do not represent these as verified to a customer until at least one successful restore drill has occurred.

---

**Before this document is used with any real customer:**
1. Section 6 (support response times) cannot be completed honestly until a real support process and staffing exists — this is a business decision, not a document-drafting task.
2. Section 1's uptime target should not be strengthened beyond 99.5% without new load-testing evidence to support it.
3. Section 8 should be caveated verbally in any sales conversation until a restore drill has actually happened — offering an RTO/RPO commitment for a backup process that's never been tested is a genuine risk to Solunar, not just an SLA drafting nicety.
