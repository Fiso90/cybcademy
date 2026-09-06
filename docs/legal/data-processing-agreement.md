# CybCademy Data Processing Agreement (DPA)

**⚠️ DRAFT FOR LEGAL REVIEW — DO NOT EXECUTE.** This draft follows standard DPA structure (broadly aligned with GDPR Article 28-style processor obligations, which POPIA's operator provisions and the NDPA's data processing agent provisions substantially mirror in structure, though not in every detail) and is written to accurately reflect the platform's actual technical controls rather than generic language. It has not been reviewed by counsel and must not be signed as-is.

**Between:** Solunar Informatics ("Processor") and Customer ("Controller"), as an addendum to the Terms of Service / Master Services Agreement.

---

## 1. Subject Matter and Duration

This DPA governs Processor's processing of personal data on Controller's behalf through the CybCademy Service, for the duration of the underlying subscription agreement.

## 2. Nature and Purpose of Processing

Processor processes Controller's employee personal data solely to provide the Service: security awareness training delivery, phishing simulation, policy management, incident tracking, and related analytics, as described in the Product Requirements Document and Architecture Document.

## 3. Categories of Data Subjects

Controller's employees, contractors, and other individuals Controller enrols as Users.

## 4. Categories of Personal Data

As detailed in the Privacy Policy, Section 2 — identity, authentication metadata, training records, phishing simulation interaction data, policy acknowledgement records, incident report content, and audit/usage logs. No special category data is intentionally collected (see Privacy Policy, Section 4, regarding residual risk in free-text fields).

## 5. Processor Obligations

Processor shall:

- **Process personal data only on documented instructions from Controller**, including regarding international transfers, unless required to do otherwise by applicable law.
- **Ensure persons authorised to process the data** (Solunar personnel) are subject to confidentiality obligations.
- **Implement appropriate technical and organisational measures**, specifically:
  - Row-Level Security enforced at the database level, in addition to application-layer tenant scoping, so that Controller's data is never accessible to another Customer even in the event of an application-layer bug (see Architecture Document, Section 8 and Database Design Document, Section 6)
  - Mandatory multi-factor authentication for all privileged internal roles
  - Encryption of data at rest and in transit
  - Immutable audit logging of security-relevant actions, with database-level privilege restrictions preventing even Processor's own application from modifying historical audit records (see Security Architecture Document, Section 7)
  - Documented, logged "break-glass" procedure for any exceptional internal access to Controller Data, per Security Architecture Document, Section 7
- **Assist Controller** in responding to data subject rights requests, to the extent Controller cannot reasonably fulfil them via the Service's own self-service tooling (e.g., employee deactivation, data export).
- **Notify Controller without undue delay** upon becoming aware of a personal data breach affecting Controller Data. [PLACEHOLDER: specific notification timeframe — commonly 24-72 hours, should align with Controller's own regulatory notification obligations under POPIA/NDPA/other applicable law, which Processor's timeframe must accommodate]
- **Assist Controller with data protection impact assessments**, where applicable, given the nature of processing (notably, Section 8 below).
- **Delete or return all Controller Data** at the end of the engagement, per the Data Retention & Deletion Policy, and delete existing copies unless applicable law requires retention.
- **Make available information necessary to demonstrate compliance** with this DPA, including permitting audits — see Section 9.

## 6. Sub-Processors

- Processor uses the Sub-Processors listed in the Sub-Processor List (Anthropic for AI features, Cloudflare for CDN/security, and Processor's chosen managed database and object storage providers).
- Processor shall notify Controller of any intended changes to Sub-Processors, giving Controller the opportunity to object on reasonable data-protection grounds. [PLACEHOLDER: notice period — commonly 14-30 days]
- Processor remains fully liable to Controller for a Sub-Processor's performance of its data-processing obligations.

## 7. International Transfers

[PLACEHOLDER: this is currently undraftable in good faith — it depends on where the managed PostgreSQL instance, object storage, and Anthropic's API endpoints actually process data relative to where Controller and its Users are located, none of which has been finalised in this project's DevOps decisions to date. Do not draft transfer mechanism language (SCCs, adequacy reliance, etc.) until hosting region is confirmed.]

## 8. AI Processing — Specific Terms

Certain Service features transmit content to a third-party AI provider (Anthropic) to generate quiz questions, policy summaries, phishing simulation templates, and executive narrative text. Processor has implemented the following technical measures specifically to minimise personal data reaching this Sub-Processor:

- A dedicated gateway component (`AiGatewayService`) is the sole path by which any content reaches the AI provider, and includes an automated check that rejects prompts containing detectable email-address patterns before transmission.
- Features that summarise organisation-wide data (Executive Narratives, Risk Recommendations) are architecturally restricted to consuming only department-level or organisation-level aggregated data, never individual employee records.

[PLACEHOLDER: this section describes real technical controls but Controller's counsel should independently assess whether this is sufficient given Controller's specific regulatory obligations, rather than accepting Processor's characterisation at face value — that's a normal and expected part of DPA negotiation, not a weakness in this draft.]

## 9. Audit Rights

Controller may request evidence of Processor's compliance with this DPA, including relevant sections of the Security Architecture Document and, where an independent third-party security assessment has been completed (see the Security Audit Checklist in the Phase 15 Release document — **not yet completed as of this draft**), a summary of its findings. [PLACEHOLDER: on-site audit rights, frequency limits, cost allocation — standard negotiated terms]

## 10. Liability

[PLACEHOLDER: liability allocation for DPA breaches specifically — often carved out from or set differently to the general liability cap in the Terms of Service, and requires counsel input]

## 11. Governing Terms

Where this DPA conflicts with the Terms of Service on data protection matters, this DPA prevails.

---

**Before this document is used with any real customer:**
1. Cannot be finalised until hosting region/infrastructure decisions (currently open per Phase 13) are locked down — Section 7 is unwritable without that.
2. Must be reviewed against POPIA's "operator" provisions and the NDPA's data processing agent provisions specifically, not generic GDPR-alignment assumptions.
3. Section 5's breach notification timeframe must be checked against Controller's own regulatory deadlines in its jurisdiction, which vary.
4. This document currently makes real, specific technical claims (RLS, break-glass logging, the AI gateway's PII guardrail) rather than generic assurances — good for credibility with a technically sophisticated customer, but every claim must be re-verified against the actual production system before signature, not assumed still true from this project's design phase.
