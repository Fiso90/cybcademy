# CybCademy — Administrator Guide

For Organisation Administrators managing your organisation's CybCademy tenant.

## 1. Onboarding Your Organisation

1. Complete your organisation profile (name, industry, applicable compliance frameworks).
2. Import employees via CSV, or add them individually under Employees.
3. New employees are automatically enrolled in any course marked as an onboarding default.
4. Invite additional Administrators, Compliance Officers, Trainers, or Security Officers as needed — each role sees only the sections relevant to their responsibilities.

**Note:** employee CSV import is the only bulk-onboarding method in this release; live HR system integration is not yet available (flagged as a known gap since the platform's earliest design phase — see the Software Requirements Specification's Risks section if you need this for procurement conversations).

## 2. Managing Employees

- **Deactivating an employee** (Employees → select employee → Deactivate) immediately revokes access and is logged. It does not delete their historical training/certificate records — those remain for compliance purposes.
- **Department structure** supports one level of hierarchy (parent/child departments), used for both training assignment targeting and Compliance Dashboard filtering.

## 3. Multi-Factor Authentication

MFA is **mandatory, not configurable**, for the following roles: System Administrator, Organisation Administrator, Compliance Officer, Internal Auditor, IS Auditor, Security Officer. This is a platform-level security guarantee, not a setting you can disable for your organisation — see the Security Architecture document if your security team needs the underlying rationale for a vendor risk review.

Password policy (minimum length, complexity, expiry) *is* configurable per organisation under Settings.

## 4. Phishing Simulation — Important Context Before You Launch a Campaign

Individual employee results from phishing simulations are **aggregated to department level by default** when viewed by Managers — this is a deliberate platform design decision (not a bug or a missing feature) to keep simulations educational rather than something employees fear will be used against them individually. Security Officers, Compliance Officers, and Administrators see individual-level results.

Employees who click a simulated phishing link are shown educational content immediately, framed constructively — not a "gotcha" message. We recommend communicating this design to your organisation before your first campaign, so employees understand simulations are a training tool.

## 5. Policy Management

- Uploading a new version of an existing policy creates a new version number; it does not overwrite the previous one.
- Publishing a policy makes it visible to employees and starts tracking acknowledgement.
- Every employee acknowledgement is permanently recorded with timestamp and IP address, and — by design — **cannot be edited or deleted**, even by an Administrator. If an acknowledgement was recorded in error, the correction is a new policy version, not an edit to the existing record. This immutability is what makes acknowledgement records defensible as audit evidence.

## 6. Compliance Dashboard and Audit Evidence

The Compliance Dashboard gives you real-time visibility into training completion and policy acknowledgement status, filterable by department.

**Generating audit evidence:** the Export Evidence button generates a downloadable package (training records, policy acknowledgements, incident logs) for a chosen date range. This typically completes in under 5 minutes; you'll see a status indicator while it processes and can continue working elsewhere in the platform in the meantime. The download link expires 15 minutes after the export completes, for security.

## 7. Subscription and Billing

Your organisation's subscription tier (Basic, Professional, Enterprise) determines which features are available — notably, AI-powered features (Quiz Generator, Policy Summariser, Risk Recommendations from Professional; Executive Reports and AI Phishing Template generation from Enterprise). If a feature shows an upgrade prompt, that's the tier gate working as intended, not an error.

## 8. Getting Help

Use the in-app Knowledge Base for self-service articles. For issues the Knowledge Base doesn't resolve, contact Solunar Informatics support.
