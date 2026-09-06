# CybCademy — POPIA / NDPA Compliance Mapping Matrix

**⚠️ REVIEW NOTE:** This maps CybCademy's actual, built technical and process controls (drawn from the Architecture, Security Architecture, and Database Design documents — not aspirational claims) against POPIA (South Africa's Protection of Personal Information Act) and the NDPA (Nigeria's Data Protection Act). It should be reviewed by counsel qualified in each specific jurisdiction before being used in a sales or compliance context — mapping platform *features* to legal *conditions* is a useful engineering exercise but is not itself a legal compliance certification, and this document should never be represented to a customer as one.

**Purpose:** Phase 1 and Phase 3 repeatedly cite POPIA/NDPA alignment as a core differentiator against global competitors, but no document in the original 15-phase plan actually performed this mapping. This closes that gap.

---

## POPIA — Conditions for Lawful Processing

POPIA structures its requirements around eight "conditions for lawful processing." Below, each is mapped to what CybCademy actually does.

| POPIA Condition | CybCademy's Relevant Control | Status |
|---|---|---|
| **1. Accountability** — a responsible party must ensure conditions are met | Solunar acts as operator (processor); Customer is the responsible party (controller) for its employees' data — this allocation is formalised in the DPA | Documented in DPA |
| **2. Processing Limitation** — lawful, minimal, with consent/legitimate interest | Data collected is limited to what training/compliance/security functions require (see Database Design Document schema — no fields exist for data unrelated to these purposes) | By design |
| **3. Purpose Specification** — collected for a specific, defined purpose | Each data category's purpose is documented (Privacy Policy Section 2); the platform does not repurpose training data for unrelated uses | Documented |
| **4. Further Processing Limitation** — compatible with original purpose | AI features process data only for the specific feature invoked (e.g., Policy Summariser processes policy text, not employee records) — enforced architecturally via `AiGatewayService`'s scoped prompt construction, not just policy | Technically enforced, not just stated |
| **5. Information Quality** — data must be accurate and up to date | Employee data is Customer-managed (CSV import, manual edit); the platform does not independently generate or infer identity data | Shared responsibility — accuracy depends on Customer's own data hygiene |
| **6. Openness** — data subject must be notified of processing | Privacy Policy provides this; **operationalising notification to individual employees is Customer's responsibility as the controller**, not something the platform automates | Process gap — recommend a documented onboarding-communication template for Customers, not yet built |
| **7. Security Safeguards** — appropriate technical/organisational measures | Encryption at rest/in transit, MFA, Row-Level Security tenant isolation, immutable audit logging — see Security Architecture Document for full detail | Extensively documented and (per Phase 12) partially tested; **not yet independently assessed (Phase 15 hard blocker)** |
| **8. Data Subject Participation** — right to access/correct/delete own data | Employees can view their own training records in-app; deletion/correction requests route through Customer (the controller) — see Privacy Policy Section 8 | Partial — no dedicated self-service data export/deletion request flow exists in the current build; this is a real gap, not fully closed |

## NDPA — Key Principles

Nigeria's NDPA (2023) principles substantially parallel POPIA's structure with some differences in emphasis and specific obligations (e.g., its explicit data protection impact assessment triggers and registration requirements for certain processors).

| NDPA Principle | CybCademy's Relevant Control | Status |
|---|---|---|
| Lawfulness, fairness, transparency | Same controls as POPIA Conditions 2-3 above | Covered |
| Purpose limitation | Same as POPIA Condition 3-4 | Covered |
| Data minimisation | Schema design deliberately excludes unnecessary fields (Database Design Document, Section 4 — documented denormalisation decisions, not unconstrained data collection) | Covered |
| Accuracy | Same as POPIA Condition 5 | Shared responsibility |
| Storage limitation | Data Retention & Deletion Policy defines configurable retention; **`human_risk_scores` and `audit_logs` are explicitly flagged in the Database Design Document as unbounded, append-only tables without a partitioning/archival policy yet implemented** — this is a real tension with storage limitation principles that hasn't been resolved | Open gap |
| Integrity and confidentiality | Same security controls as POPIA Condition 7 | Covered (same independent-assessment caveat applies) |
| Accountability | DPA + documented audit logging | Covered |
| **Registration** (NDPA-specific: certain data controllers/processors must register with the Nigeria Data Protection Commission) | Not addressed anywhere in this project | **Hard compliance requirement, not a nice-to-have, if Solunar processes data at the volume thresholds the NDPA specifies for mandatory registration — needs immediate legal assessment before any Nigerian customer onboarding, separate from anything this platform's code can address** |

## Cross-Cutting Gaps (Apply to Both Frameworks)

1. **No Data Protection Impact Assessment (DPIA) has been performed** for the Human Risk Score feature specifically, despite it functioning as a form of automated profiling — recommend this as a priority, not because the feature is necessarily high-risk, but because *not having assessed whether it's high-risk* is itself the gap.
2. **No employee-facing "how your data is used" onboarding flow exists** — the Privacy Policy exists as a document, but there's no in-product moment where a new employee is walked through what CybCademy collects about them, which is a weaker openness/transparency posture than a one-time document buried in onboarding paperwork.
3. **The independent security assessment remains outstanding** (Phase 15's hard blocker) — both frameworks' security-safeguards conditions are currently supported only by Solunar's own internal testing.

## How to Use This Document

This is an engineering-to-legal handoff artifact, not a finished compliance certification. Its value is that every claim in it points at a specific, real control or a specific, real gap — not generic "we take security seriously" language. Hand this to counsel as a starting point for a proper legal compliance assessment, and expect them to find additional gaps this technical mapping wouldn't surface (e.g., corporate registration requirements, cross-border transfer mechanics once hosting region is finalised).
