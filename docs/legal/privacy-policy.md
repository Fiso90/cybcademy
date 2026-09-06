# CybCademy Privacy Policy

**⚠️ DRAFT FOR LEGAL REVIEW — DO NOT PUBLISH.** This draft is written to accurately reflect the system's actual architecture (as documented in the Architecture, Database Design, and Security Architecture documents) rather than generic boilerplate — but it has not been reviewed by a privacy lawyer or checked against POPIA (South Africa), the NDPA (Nigeria), or any other applicable data protection law in Solunar's actual operating markets. Bracketed items require completion.

**Last Updated:** [DATE] · **Version:** 1.0-draft

---

## 1. Who This Policy Covers

This policy describes how Solunar Informatics processes personal data through the CybCademy platform. It applies to:
- **Customer** organisations who subscribe to CybCademy ("Customer," acting as data controller for its own employees' data)
- **Users** — employees of a Customer who use the platform ("you")

Solunar acts as a **data processor** on behalf of Customer for the personal data of Customer's employees (see the Data Processing Agreement for the detailed processor obligations). This policy explains, in plain language, what data the platform holds about you and why.

## 2. What Personal Data We Process

Directly drawn from the actual database schema (Database Design document), not a generic list:

| Category | Examples | Why |
|---|---|---|
| Identity | Name, email, department, role | Account creation, access control |
| Authentication | Password (hashed, never stored in plain text — Argon2id), MFA status | Login security |
| Training records | Course assignments, completion status, assessment scores, certificates | Compliance evidence, the Human Risk Score |
| Phishing simulation results | Whether you opened/clicked/reported a simulated phishing email | Human Risk Score, department-level training targeting |
| Policy acknowledgements | Which policy versions you've confirmed reading, with timestamp and IP address | Compliance evidence — **this record is permanent and cannot be edited or deleted**, by design, since its evidentiary value depends on immutability |
| Incident reports | Content of anything you report as suspicious | Security incident handling |
| Usage/audit data | Login times, actions taken in the platform | Security monitoring, audit evidence |

## 3. Automated Decision-Making — The Human Risk Score

CybCademy calculates a "Human Risk Score" for each employee, derived from training completion, assessment performance, and phishing simulation results (documented formula: 40% training / 30% assessment / 30% phishing resilience). [PLACEHOLDER: confirm whether this constitutes "automated decision-making with legal or similarly significant effects" under applicable law — if the score influences employment decisions, additional disclosure and possibly a right to human review may be required; this needs specific legal analysis, not a generic disclaimer.]

By design, this score is **not** intended to be used for individual disciplinary action — phishing simulation results specifically are aggregated to department level by default when viewed by an employee's direct manager (see Section 5). Customer configures how it uses this score internally; Solunar does not control or monitor Customer's internal use of it.

## 4. Special Category / Sensitive Data

CybCademy is not designed to collect special category data (health, biometric beyond authentication, etc.). Incident reports and free-text fields could theoretically contain such data if a User includes it voluntarily — Customer is responsible for guiding Users not to include unnecessary sensitive information in free-text fields. [PLACEHOLDER: legal review of whether any additional technical/organisational measures are required for this residual risk]

## 5. Who Can See Your Data

Access is role-based (see the Security Architecture document for the full model):

- Your **direct manager**, by default, sees department-level phishing simulation trends only — not your individual results — unless your organisation has specifically reconfigured this.
- **Security Officers, Compliance Officers, and Administrators** at your organisation can see individual-level results across the relevant modules.
- **Solunar Informatics** personnel do not routinely access individual Customer Data; any exception (e.g., support troubleshooting) follows a documented "break-glass" procedure that is itself logged (see the Security Architecture document, Section 7).
- Your data is **never** visible to any other Customer organisation using CybCademy — tenant data isolation is enforced at both the application and database level (see the Architecture document).

## 6. Third Parties We Share Data With (Sub-Processors)

See the separate Sub-Processor List for the current, maintained list. As of this document's drafting, these include, at minimum: [PLACEHOLDER — confirm and complete against actual production configuration]
- **Anthropic** (AI features — quiz generation, policy summarisation, executive narratives). Data sent to this provider is limited by design to aggregated/non-identifying content where the feature allows it (see `AiGatewayService`'s data-minimisation design); [PLACEHOLDER: confirm and disclose exactly what individual-level content, if any, reaches this provider for features like the AI Phishing Template Generator, which does not process employee data directly but should be checked].
- **Cloudflare** (CDN, DDoS protection, SSL termination)
- **[Object storage provider]** (file storage — certificates, policy documents, uploaded content)
- **[Managed database provider]**

## 7. Data Retention

- Retention periods are configurable per Customer organisation (see the Data Retention & Deletion Policy for the default schedule and rationale).
- Policy acknowledgement records and audit logs are retained for compliance/evidentiary purposes even after an employee is deactivated, unless Customer specifically requests deletion and that request is consistent with Customer's own legal retention obligations.
- Upon Customer's subscription ending, data is retained for [PLACEHOLDER: X days] to allow export, then deleted per the Data Retention & Deletion Policy.

## 8. Your Rights

Depending on your jurisdiction, you may have rights to access, correct, delete, or port your personal data, or to object to certain processing. **Because Solunar acts as a processor on behalf of your employer for most of this data, requests should generally go to your employer (the data controller) first** — they are best placed to action most requests and are contractually required to facilitate them. [PLACEHOLDER: jurisdiction-specific rights language for POPIA, NDPA, and any others applicable]

## 9. Security

See the Security Architecture document for full technical detail. In summary: encryption in transit and at rest, mandatory MFA for privileged roles, immutable audit logging, and Row-Level Security enforcing tenant data isolation at the database level.

## 10. International Data Transfers

[PLACEHOLDER: this section requires real answers about where data is actually hosted (which region the managed PostgreSQL and object storage instances run in) and where the AI provider processes requests — cannot be drafted meaningfully without that operational detail, which has not been finalised as of Phase 13's DevOps work]

## 11. Changes to This Policy

We will notify Customer administrators of material changes to this policy. [PLACEHOLDER: notice period]

## 12. Contact

[PLACEHOLDER: Solunar Informatics' registered address, and a named or role-based contact (e.g., a Data Protection Officer or equivalent) for privacy inquiries — required under most applicable frameworks]

---

**Before this document is used with any real customer:**
1. This must be reviewed by counsel with specific POPIA and NDPA expertise, not general privacy-law knowledge — the target markets named in Phase 1 have specific requirements this draft has not been checked against.
2. Every `[PLACEHOLDER]` needs a real, verified answer — several depend on operational decisions (hosting region, sub-processor list) that haven't been finalised elsewhere in this project.
3. This document and the DPA must be reconciled — they describe the same processing from two angles and must not contradict each other.
