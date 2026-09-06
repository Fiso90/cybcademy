# CybCademy — Phase 3: Enterprise Product Requirements Document (PRD)

**Product:** CybCademy — Human Cyber Risk Management & Cybersecurity Awareness Platform
**Developed by:** Solunar Informatics
**Document Version:** 1.0
**Date:** 28 July 2026
**Status:** Draft for Approval
**Depends on:** Phase 1 (Product Discovery, approved), Phase 2 (SRS, approved)

---

## 1. Executive Summary

CybCademy is an enterprise-grade, multi-tenant Human Cyber Risk Management platform purpose-built for African organisations. It combines security awareness training, phishing simulation, policy compliance tracking, incident reporting, and executive-level risk analytics into a single platform — positioned to fill the gap left by global incumbents (KnowBe4, Proofpoint) whose pricing, localisation, and regulatory alignment do not serve African mid-market and regulated-sector customers well.

v1.0 targets a single-region, English-language, web-first release, built on Laravel/PHP 8.3, PostgreSQL 16, and a Blade/Bootstrap frontend, with a clear technical path to multi-region and multilingual expansion post-GA.

## 2. Vision

*(Carried forward from Phase 1 — unchanged.)* CybCademy exists to become Africa's leading human cyber risk management platform — treating employees as a measurable, improvable risk surface rather than a training checkbox.

## 3. Goals

| Goal | Description |
|---|---|
| G1 | Ship a secure, audit-ready v1.0 covering all in-scope modules within the agreed roadmap |
| G2 | Achieve OWASP ASVS Level 2 posture prior to GA, verified by independent assessment |
| G3 | Onboard pilot customers in at least two sectors (financial services, public sector) within two quarters of GA |
| G4 | Establish CybCademy's Human Risk Score as a credible, defensible metric that customers reference in their own board reporting |

## 4. KPIs

- Time-to-first-value: new tenant fully onboarded (employees imported, first course assigned) in under 1 business day
- Audit evidence export time: under 5 minutes (per SRS FR-8.2)
- Trended reduction in phishing simulation click-through rate per tenant over rolling 6-month windows
- Monthly Active Organisations and Net Revenue Retention (business KPIs, tracked post-launch)
- Platform uptime ≥ 99.5% (v1.0 target per SRS NFR)

## 5. Features (Consolidated by Module)

| Module | Key Features |
|---|---|
| Authentication | Email/password, MFA (TOTP), OAuth2 SSO (Enterprise tier), JWT for API, RBAC across 11 roles |
| Organisation & Employee Management | Tenant onboarding, CSV bulk import, department hierarchy, lifecycle-triggered training assignment |
| Learning Management | Course Builder, Course Player, Question Bank, Assessments, Certificates |
| Policy Management | Versioned policy upload, acknowledgement tracking, automated reminders |
| Phishing Simulation | Campaign builder, AI-generated templates, real-time results, non-punitive just-in-time education |
| Incident Reporting | Employee-submitted reports, Security Officer triage workflow |
| Analytics | Human Risk Score (individual/department/org), Compliance Dashboard, Executive Dashboard, scheduled reports |
| Audit Centre | Immutable audit logs, one-click evidence export packages |
| AI Features | Security Tutor, Quiz Generator, Policy Summariser, Phishing Generator, Risk Recommendations, Executive Report narratives, Chat Assistant |
| Platform | Multi-tenancy, tiered billing, REST API, Knowledge Base, Notifications |

## 6. Roadmap

| Milestone | Scope | Target Phase Alignment |
|---|---|---|
| M1 — Architecture & Design Complete | Phases 4–8 documentation approved | Design sign-off |
| M2 — Core Platform Alpha | Auth, Org/Employee Mgmt, Multi-tenancy, RBAC | Phase 10 (early modules) |
| M3 — Learning & Compliance Beta | LMS, Policy Mgmt, Certificates | Phase 10–11 |
| M4 — Risk & Simulation Beta | Phishing Simulation, Incident Reporting, Human Risk Analytics | Phase 10–11 |
| M5 — Executive & Audit Complete | Compliance/Executive Dashboards, Audit Centre, Reports | Phase 10–11 |
| M6 — AI Features Integrated | All 7 AI features live behind feature flags per tier | Phase 10–11 |
| M7 — Hardening & Release | Security testing, performance testing, ASVS assessment, documentation | Phases 12–15 |
| GA — v1.0 Release | Production launch | Phase 15 |

*(Detailed epic/sprint breakdown is produced in Phase 9 — Development Plan; this roadmap sets milestone-level sequencing only.)*

## 7. Personas

*(Carried forward from Phase 1, referenced here for design continuity: Thandiwe — Compliance Officer; Kwame — Security Officer; Ngozi — HR Manager; Frontline Employee; External IS Auditor. See Phase 1 document for full detail.)*

## 8. User Flows

### 8.1 New Tenant Onboarding
1. Organisation Administrator signs up / is provisioned by Solunar sales.
2. Admin completes organisation profile (name, industry, compliance frameworks applicable).
3. Admin bulk-imports employees via CSV (or adds manually).
4. System auto-assigns onboarding course bundle based on configured rules.
5. Admin invites additional Trainers/Compliance Officers/Managers as needed.
6. Dashboard shows onboarding completion progress in real time.

### 8.2 Employee Training Completion
1. Employee receives notification of assigned course.
2. Employee logs in (MFA if enforced), opens Course Player.
3. Employee progresses through lessons; progress auto-saves.
4. Employee completes assessment drawn from Question Bank.
5. On passing score, certificate is generated and stored against employee record.
6. Compliance Dashboard updates completion status in real time.

### 8.3 Phishing Simulation Campaign
1. Security Officer selects or AI-generates a phishing template.
2. Officer targets campaign by department/role/individual and schedules send.
3. System sends simulated phishing emails and logs interactions (open/click/submit/report).
4. Employees who click are redirected to immediate, non-punitive educational content.
5. Results aggregate into department-level Human Risk Score adjustments (per BR-4, individual results not exposed to managers by default).
6. Security Officer reviews campaign results dashboard.

### 8.4 Audit Evidence Export
1. Compliance Officer or Auditor navigates to Audit Centre.
2. Selects tenant scope, date range, and evidence categories (training, policy, incidents).
3. System compiles immutable audit log records into export package.
4. Export generated as PDF/CSV within target 5-minute SLA.
5. Package downloaded with tamper-evident metadata (generation timestamp, generating user, record count).

## 9. Wireframes (Structural Description)

*(Low-fidelity structural wireframes — visual mockups to be produced in Phase 6 UI/UX using the confirmed design system.)*

**Executive Dashboard — Layout**
```
┌─────────────────────────────────────────────┐
│ Top Nav: Logo | Org Switcher | Notifications │
├───────────┬─────────────────────────────────┤
│ Side Nav  │  Human Risk Score (large, trend) │
│ - Dash    │  ┌───────────┐ ┌───────────────┐ │
│ - Courses │  │ Dept Risk │ │ AI Narrative  │ │
│ - Phishing│  │ Heatmap   │ │ Summary       │ │
│ - Policies│  └───────────┘ └───────────────┘ │
│ - Audit   │  Recent Incidents | Compliance % │
│ - Reports │                                  │
└───────────┴─────────────────────────────────┘
```

**Course Player — Layout**
```
┌─────────────────────────────────────────────┐
│ Course Title | Progress Bar                  │
├───────────┬─────────────────────────────────┤
│ Lesson    │  Content Area (video/text/quiz)  │
│ List      │                                  │
│ (sidebar) │  [Prev] [Next] [Mark Complete]   │
└───────────┴─────────────────────────────────┘
```

**Audit Centre — Layout**
```
┌─────────────────────────────────────────────┐
│ Filters: Date Range | Category | Tenant Scope│
├───────────────────────────────────────────────┤
│ Preview Table (record count, categories)      │
│ [Generate Export] → PDF | CSV                 │
└───────────────────────────────────────────────┘
```

## 10. Functional Requirements

Consolidated in full in the Phase 2 Software Requirements Specification (Section 3, FR-1.x through FR-10.x). This PRD adopts that requirement set without modification.

## 11. Non-Functional Requirements

Consolidated in full in the Phase 2 SRS (Section 4). Highlights carried into design priority:
- PostgreSQL Row-Level Security-enforced multi-tenancy
- OWASP ASVS Level 2 target
- WCAG 2.1 AA accessibility
- p95 dashboard load under 2s, API under 500ms

## 12. Compliance Requirements

- Platform must support configurable compliance framework templates (initial targets: POPIA - South Africa, NDPA - Nigeria, with extensibility for additional regional frameworks).
- Audit Centre evidence exports must map directly to common auditor evidence requests (training completion, policy sign-off, incident handling, access control changes).
- Data retention and deletion must be configurable per tenant to support "right to erasure"-style regional requirements.

## 13. Security Requirements

Full detail deferred to Phase 7 (Security Architecture); at PRD level, the following are binding constraints:
- MFA mandatory for all privileged roles (BR-2)
- Encryption in transit (TLS 1.2+) and at rest for all tenant data
- Immutable audit logging for all security-relevant actions
- Tenant data isolation enforced at both application and database layer

## 14. Accessibility Requirements

- WCAG 2.1 AA compliance across all employee-facing screens (course player, phishing report button, policy acknowledgement flows are highest priority given broad, non-technical user base)
- Keyboard navigability and screen-reader compatibility required for all core workflows
- Colour contrast and text sizing compliant with AA thresholds in both Light and Dark modes

## 15. Performance Requirements

- Dashboard pages: < 2s load, p95
- API responses (non-report endpoints): < 500ms, p95
- Report/export generation: < 5 minutes for audit evidence packages up to 5,000 employees (per SRS FR-8.2)
- Course Player video content: adaptive delivery suitable for variable-bandwidth conditions typical of target markets

## 16. Scalability

- Application tier (Laravel/PHP-FPM containers) must scale horizontally behind load balancer independent of database tier
- Database (PostgreSQL) scaling path: vertical scaling for v1.0, with read-replica support architected as a near-term follow-on rather than a v1.0 requirement
- Queue workers (Redis-backed) must scale independently to absorb phishing campaign sends and report generation spikes without impacting interactive request latency

## 17. Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Content production bottleneck (courses, phishing templates) delays launch | Medium | High | Parallelise content production with engineering from M1; consider initial licensed content library to bridge gap |
| Tenant data isolation failure (cross-tenant data leak) | Low (with RLS) | Critical | PostgreSQL RLS + application-layer scoping + automated leak-detection tests (per architecture decision) |
| Regulatory mapping insufficient for a target jurisdiction | Medium | Medium | Configurable compliance framework templates rather than hard-coded logic |
| Phishing simulation perceived as punitive, damaging customer's employee trust | Medium | Medium | BR-4 default (aggregate-only manager visibility) + educational follow-up content, documented clearly in sales/onboarding |
| Independent security assessment surfaces material findings pre-GA | Medium | High | Schedule assessment early enough in Phase 12 to allow remediation time before Phase 15 release |

## 18. Release Plan

- **Alpha (internal):** M2–M3 milestones, internal Solunar testing only
- **Closed Beta:** M4–M5 milestones, 2–3 pilot customers under NDA, feedback loop into Phase 11/12
- **Security Hardening & Assessment:** Phase 12, independent ASVS Level 2 assessment
- **GA (v1.0):** Phase 15, following completion of all release/go-live checklists

---

## Deliverables

- This Enterprise Product Requirements Document

## Assumptions

- Pilot customers identified in Phase 1's business case will be available for closed beta participation
- Content production capacity is secured in parallel with M1–M3, not sequenced after engineering
- Compliance framework templates for POPIA/NDPA can be defined with available public regulatory documentation without requiring dedicated legal counsel for v1.0 (legal review recommended before GA regardless)

## Risks

*(See Section 17 — Risk Assessment, above, which fulfils this phase's risk deliverable in full.)*

## Next Phase

**Phase 4 — System Architecture:** Architecture diagram, monolith decision documentation, folder structure, MVC/REST architecture, authentication/authorization flow diagrams, database architecture, caching, file storage, deployment architecture, logging and monitoring — formalising the technology decisions already confirmed in discussion (Laravel, PostgreSQL 16, Blade + Bootstrap 5, Docker/Nginx, Redis, Cloudflare).
