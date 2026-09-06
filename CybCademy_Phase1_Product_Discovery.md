# CybCademy — Phase 1: Product Discovery Document

**Product:** CybCademy — Human Cyber Risk Management & Cybersecurity Awareness Platform
**Developed by:** Solunar Informatics
**Document Version:** 1.0
**Date:** 28 July 2026
**Status:** Draft for Approval

---

## 1. Vision

CybCademy exists to become **Africa's leading human cyber risk management platform** — the system organisations rely on to understand, measure, and reduce the risk their own people represent to cybersecurity, rather than treating security awareness as a once-a-year compliance checkbox.

Where most security training is a static video followed by a quiz nobody remembers, CybCademy treats the employee as a *measurable risk surface*: continuously assessed, continuously trained, and continuously reported on to the people accountable for organisational risk — compliance officers, auditors, and executives.

## 2. Mission

To give African organisations — from mid-size enterprises to regulated financial institutions and public sector bodies — an affordable, locally-relevant, enterprise-grade platform to:

- Train employees on real, current cyber threats
- Simulate phishing and social engineering attacks safely
- Measure human cyber risk quantitatively, per employee and per department
- Manage and track policy acknowledgement
- Generate audit-ready compliance evidence on demand
- Give executives a single, honest view of organisational cyber exposure

## 3. Objectives

| # | Objective | Measure of Success |
|---|---|---|
| 1 | Reduce organisational susceptibility to phishing | Click-through rate on simulated phishing drops measurably over 6–12 months per tenant |
| 2 | Provide audit-ready compliance evidence | Auditors can generate evidence packages (training completion, policy sign-off, incident logs) in under 5 minutes |
| 3 | Quantify human cyber risk | Every employee and department has a live, defensible Human Risk Score |
| 4 | Achieve enterprise-grade security posture | Platform itself passes an independent security assessment against OWASP ASVS Level 2 before GA |
| 5 | Reach commercial traction in target markets | Signed pilot customers in at least 2 sectors (financial services, public sector) within first 2 quarters post-launch |

## 4. Business Case

**Problem:** Human error remains one of the leading contributing factors in breaches, yet most organisations — particularly in African markets — either have no formal security awareness programme, or run one that is a compliance formality disconnected from actual risk reduction. Existing global platforms (KnowBe4, Proofpoint Security Awareness, Hoxhunt) are priced and positioned for North American/European enterprise budgets, often lack local payment methods, local regulatory alignment (e.g. POPIA in South Africa, NDPA in Nigeria, data protection acts across East Africa), and local threat-context content (region-specific phishing lures, local financial institution impersonation, etc.).

**Opportunity:** A platform purpose-built for African regulatory and market conditions — priced accessibly, regionally hosted or hostable, and content-localised — has a clear gap to fill rather than competing head-on with global incumbents on their terms.

**Investment justification:** Solunar Informatics already possesses PHP/Laravel engineering capacity and cybersecurity consulting expertise; CybCademy converts existing consulting relationships into a recurring-revenue SaaS product, improving margin and retention relative to project-based consulting work.

## 5. Competitive Analysis

| Platform | Strengths | Weaknesses (relative to CybCademy's positioning) |
|---|---|---|
| KnowBe4 | Market leader, huge content library, mature phishing simulation | Expensive for African SME/mid-market budgets, US/EU-centric content and support, complex to procure for smaller regional buyers |
| Proofpoint Security Awareness | Strong enterprise integration, deep analytics | Enterprise-only pricing and complexity, minimal local presence in African markets |
| Hoxhunt | Good gamification and UX | Narrower compliance/audit tooling, limited localisation |
| Local/manual training providers (consultants running one-off workshops) | Low cost, personal relationships | No continuous measurement, no platform, no phishing simulation, no audit trail — essentially a single event, not a system |

**CybCademy's differentiated position:** enterprise-grade functionality (comparable module depth to KnowBe4/Proofpoint) at a price point and regulatory alignment suited to African markets, with a compliance/audit-first design (Audit Centre as a first-class module, not an afterthought).

## 6. Stakeholders

| Stakeholder | Interest |
|---|---|
| Solunar Informatics (Product Owner) | Commercial success, product-market fit, defensible IP |
| Organisation Administrators (customer-side) | Easy setup, low admin overhead, clear ROI reporting |
| Compliance Officers / Internal Auditors | Defensible, exportable audit evidence |
| IS Auditors (external) | Independently verifiable controls and logs |
| HR | Simple employee onboarding/offboarding integration with training assignment |
| Security Officers | Accurate, real-time human risk visibility |
| Employees (end users) | Non-punitive, reasonably paced training; fair phishing simulation practices |
| Executives / Board | High-level, non-technical risk dashboards defensible in board reporting |
| Regulators (indirect stakeholder) | Platform-generated evidence should map cleanly to regional data protection and cyber-governance requirements |

## 7. User Personas

**1. Thandiwe — Compliance Officer, Regional Bank**
Needs to produce quarterly evidence of security awareness training completion and policy acknowledgement for internal and external auditors. Currently does this manually via spreadsheets. Wants one-click evidence export.

**2. Kwame — IT/Security Officer, Mid-size Logistics Company**
Wants to know which departments are most at risk of phishing, not just whether training was "completed." Wants dashboards he can defend to the CEO in five minutes.

**3. Ngozi — HR Manager**
Needs training assignment to happen automatically when new employees are onboarded, and wants to avoid being the one manually chasing completion via email.

**4. Employee — Frontline Bank Teller**
Wants short, relevant, non-condescending training that doesn't eat into work time, and doesn't want to feel "tricked and punished" by phishing simulations without context or support.

**5. External IS Auditor**
Needs to independently verify training records, policy sign-offs, and incident logs are tamper-evident and complete, without needing hand-holding from the client's internal team.

## 8. Business Model

CybCademy operates as a **multi-tenant B2B SaaS** platform, sold directly to organisations (not individual consumers), with optional professional services (onboarding, content localisation, phishing campaign design) as a services layer on top of the core subscription.

## 9. Revenue Model

| Stream | Description |
|---|---|
| Per-employee subscription (primary) | Tiered pricing based on active employee seats, billed monthly/annually |
| Tiered feature plans | Basic (training + policy) / Professional (+ phishing simulation + analytics) / Enterprise (+ audit centre, API, SSO, custom branding) |
| Professional services | Onboarding, custom content/localisation, custom phishing campaign design |
| Add-on modules | Advanced AI features (AI Executive Reports, AI Phishing Generator) as premium add-ons on top of Enterprise tier |

## 10. Success Metrics

- Monthly Active Organisations (tenants)
- Employee training completion rate per tenant
- Phishing simulation click-through-rate trend (should decrease over time per tenant)
- Net Revenue Retention (NRR)
- Time-to-generate-audit-evidence (target: under 5 minutes)
- Customer-reported reduction in real (non-simulated) phishing incidents

## 11. SWOT Analysis

| Strengths | Weaknesses |
|---|---|
| Purpose-built for African regulatory/market context | New entrant, no brand recognition versus KnowBe4/Proofpoint |
| Audit/compliance-first architecture | Smaller initial content library than established players |
| Existing consulting relationships to convert to pilot customers | Requires sustained content production investment (courses, phishing templates) |

| Opportunities | Threats |
|---|---|
| Underserved regional market with rising regulatory pressure (data protection acts across the continent) | Global incumbents entering or discounting into African markets |
| Partnerships with regional auditors/compliance consultancies as channel | Well-funded competitors with larger content and R&D budgets |
| Expansion into adjacent HCRM services (vendor risk, third-party awareness) | Slow enterprise sales cycles in regulated industries delaying revenue |

## 12. Value Proposition

*"CybCademy gives African organisations enterprise-grade human cyber risk management — training, phishing simulation, policy compliance, and audit-ready evidence — in one platform, priced and localised for the markets global platforms overlook."*

## 13. Project Scope

**In scope for v1.0:**
- Multi-tenant platform supporting the full role set defined (SysAdmin through Guest)
- Learning Management (course builder, course player, assessments, question bank, certificates)
- Policy management and acknowledgement tracking
- Phishing simulation and security awareness campaigns
- Incident reporting
- Compliance dashboard, human risk analytics, executive dashboard, reporting
- Notifications, knowledge base, audit centre
- REST API, core AI features (tutor, quiz generator, policy summariser, phishing generator, risk recommendations, executive reports, chat assistant)
- Billing and multi-tenancy infrastructure

## 14. Out of Scope (v1.0)

- Native mobile applications (mobile-responsive web only for v1.0; native apps considered post-GA)
- Deep SIEM/SOAR integrations (log export only in v1.0; live integration is a post-v1.0 roadmap item)
- White-labelling for reseller partners
- Non-English content localisation beyond initial launch language(s) — additional languages roadmap-dependent on customer demand
- Vendor/third-party risk management module (adjacent product opportunity, not v1.0)

---

## Deliverables

- This Product Discovery Document

## Assumptions

- Solunar Informatics has or will secure initial pilot customers in at least one regulated sector (banking or public sector) to validate the compliance-first positioning
- Regional data protection regulations (POPIA, NDPA, and equivalents) will remain the primary compliance frameworks customers ask to map against
- Team has or will acquire Laravel/PostgreSQL expertise sufficient for the confirmed technical architecture

## Risks

- Content production (courses, phishing templates, localisation) is resource-intensive and could become the actual bottleneck rather than engineering
- Regulatory landscape across different African jurisdictions is not uniform; a one-size-fits-all compliance mapping may not satisfy all target markets equally
- Phishing simulation carries reputational/employee-relations risk if implemented punitively rather than educationally — needs careful UX and policy design (flagged for Phase 6/UI and Phase 7/Security)
- Competing against well-resourced global incumbents on content breadth in early phases

## Next Phase

**Phase 2 — Requirements Analysis:** Functional requirements, non-functional requirements, user stories, acceptance criteria, business rules, constraints, and assumptions, culminating in the Software Requirements Specification.
