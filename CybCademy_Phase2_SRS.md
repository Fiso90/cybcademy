# CybCademy — Phase 2: Software Requirements Specification (SRS)

**Product:** CybCademy — Human Cyber Risk Management & Cybersecurity Awareness Platform
**Developed by:** Solunar Informatics
**Document Version:** 1.0
**Date:** 28 July 2026
**Status:** Draft for Approval
**Depends on:** Phase 1 — Product Discovery Document (approved)

---

## 1. Purpose

This document specifies the functional and non-functional requirements for CybCademy v1.0, derived from the scope defined in Phase 1. It is the authoritative reference for Phase 3 (PRD), Phase 5 (Database Design), and all subsequent development phases.

## 2. Scope Reference

Covers all modules listed as in-scope in the Product Discovery Document: Authentication, Organisation Management, Employee Management, Learning Management, Course Builder, Course Player, Assessments, Question Bank, Certificates, Policy Management, Policy Acknowledgement, Phishing Simulation, Security Awareness Campaigns, Incident Reporting, Compliance Dashboard, Human Risk Analytics, Executive Dashboard, Reports, Notifications, AI Assistant, Knowledge Base, Audit Centre, API, Settings, Billing, Multi-tenancy.

---

## 3. Functional Requirements

### 3.1 Authentication & Access
- FR-1.1: System shall support email/password login with bcrypt/Argon2id password hashing.
- FR-1.2: System shall support Multi-Factor Authentication (TOTP-based) enforced for all privileged roles (System Administrator, Organisation Administrator, Compliance Officer, Auditor, Security Officer) and optional for Employee/Manager roles.
- FR-1.3: System shall support OAuth2-based Single Sign-On for Enterprise-tier tenants.
- FR-1.4: System shall issue JWT tokens for external API/integration access, separate from web session authentication.
- FR-1.5: System shall enforce configurable password policies per tenant (minimum length, complexity, expiry).
- FR-1.6: System shall support account lockout after repeated failed login attempts, with rate limiting.
- FR-1.7: System shall support Role-Based Access Control across all 11 defined roles (System Administrator, Organisation Administrator, HR, Compliance Officer, Internal Auditor, IS Auditor, Security Officer, Trainer, Manager, Employee, Guest).

### 3.2 Organisation & Employee Management
- FR-2.1: System shall support onboarding of a new tenant organisation with isolated data (see Non-Functional: Multi-tenancy).
- FR-2.2: Organisation Administrators shall be able to create, edit, deactivate, and delete employee accounts.
- FR-2.3: System shall support bulk employee import (CSV) and department/team hierarchy assignment.
- FR-2.4: System shall support employee lifecycle events (onboarding, role change, offboarding) triggering automatic training/policy assignment or revocation.

### 3.3 Learning Management
- FR-3.1: Trainers/Admins shall be able to build courses using a Course Builder (modules, lessons, media, quizzes).
- FR-3.2: System shall support a Course Player supporting video, text, and interactive content, with progress tracking and resume.
- FR-3.3: System shall support assessments drawn from a reusable Question Bank (multiple choice, true/false, scenario-based).
- FR-3.4: System shall generate completion certificates automatically upon course/assessment completion, with unique verifiable IDs.
- FR-3.5: System shall support course assignment rules (by role, department, or individual).

### 3.4 Policy Management
- FR-4.1: Admins shall be able to upload/version policy documents.
- FR-4.2: System shall track policy acknowledgement per employee with timestamp and immutable audit trail.
- FR-4.3: System shall support automated reminders for outstanding policy acknowledgements.

### 3.5 Phishing Simulation & Security Awareness
- FR-5.1: Security Officers/Trainers shall be able to design and launch phishing simulation campaigns using pre-built or AI-generated templates.
- FR-5.2: System shall track per-employee interaction with simulated phishing (opened, clicked, submitted credentials, reported).
- FR-5.3: System shall trigger just-in-time educational content when an employee fails a simulation (educational, non-punitive framing).
- FR-5.4: System shall allow employees to report suspected real phishing via a one-click reporting mechanism.

### 3.6 Incident Reporting
- FR-6.1: Employees shall be able to submit security incident reports (phishing, suspicious activity, policy violation).
- FR-6.2: Security Officers shall be able to triage, assign, and track incident resolution status.

### 3.7 Analytics & Dashboards
- FR-7.1: System shall calculate a Human Risk Score per employee, department, and organisation, based on training completion, assessment performance, and phishing simulation results.
- FR-7.2: System shall provide a Compliance Dashboard showing training/policy completion status against configurable compliance frameworks.
- FR-7.3: System shall provide an Executive Dashboard with non-technical, board-level risk visualisations.
- FR-7.4: System shall support scheduled and on-demand report generation and export (PDF/CSV).

### 3.8 Audit Centre
- FR-8.1: System shall maintain immutable audit logs of all security-relevant actions (logins, permission changes, policy acknowledgements, data exports).
- FR-8.2: Auditors shall be able to generate a complete evidence export package for a given date range/tenant in under 5 minutes.

### 3.9 AI Features
- FR-9.1: System shall provide an AI Security Tutor answering employee questions on security topics within course context.
- FR-9.2: System shall provide an AI Quiz Generator producing assessment questions from course content.
- FR-9.3: System shall provide an AI Policy Summariser generating plain-language summaries of uploaded policy documents.
- FR-9.4: System shall provide an AI Phishing Email Generator for campaign creation (internal simulation use only, access-restricted).
- FR-9.5: System shall provide AI Risk Recommendations surfaced on the Compliance/Executive dashboards.
- FR-9.6: System shall provide AI-generated Executive Report narratives summarising organisational risk posture.
- FR-9.7: System shall provide a general-purpose AI Chat Assistant for platform navigation and support queries.

### 3.10 Platform & Billing
- FR-10.1: System shall support tenant-level subscription tier management (Basic/Professional/Enterprise).
- FR-10.2: System shall support usage-based billing calculation (active employee seats).
- FR-10.3: System shall provide a REST API covering Organisation, Employee, Course, Assessment, Certificate, Reporting, and Incident domains.
- FR-10.4: System shall provide a Knowledge Base module for self-service help content.
- FR-10.5: System shall provide in-app and email notifications for key events (assignments, deadlines, incidents).

---

## 4. Non-Functional Requirements

| Category | Requirement |
|---|---|
| **Multi-tenancy** | Complete data isolation between tenant organisations, enforced at both application and database level (PostgreSQL Row-Level Security) |
| **Security** | OWASP ASVS Level 2 compliance target; encryption at rest and in transit; CSRF/XSS/SQL injection protections; secure session/cookie handling; Content Security Policy headers |
| **Availability** | Target 99.5% uptime for v1.0 (single-region); documented path to 99.9% for later multi-region deployment |
| **Performance** | Dashboard pages shall load in under 2 seconds under normal load (p95); API responses under 500ms (p95) for non-report endpoints |
| **Scalability** | Architecture shall support horizontal scaling of application containers independent of database tier |
| **Accessibility** | WCAG 2.1 AA compliance across all employee-facing screens |
| **Auditability** | All security-relevant events logged immutably with actor, timestamp, and before/after state where applicable |
| **Data Retention** | Configurable per-tenant data retention policy; soft-delete (deleted_at) on all tenant data by default |
| **Localisation readiness** | Content and UI strings structured for future translation, even though only English ships in v1.0 |
| **Browser support** | Latest two versions of Chrome, Firefox, Edge, Safari; responsive down to mobile viewport widths |
| **Backup & Recovery** | Automated daily backups; documented Recovery Point Objective (RPO) ≤ 24h and Recovery Time Objective (RTO) ≤ 4h for v1.0 |

---

## 5. User Stories & Acceptance Criteria (Representative Sample)

**US-1:** *As a Compliance Officer, I want to export a complete audit evidence package for a date range, so that I can respond to an external auditor request without manual data gathering.*
- AC1: Export includes training completion records, policy acknowledgements, and incident logs for the selected range.
- AC2: Export generates in under 5 minutes for a tenant with up to 5,000 employees.
- AC3: Export is available in PDF and CSV formats.

**US-2:** *As an Employee, I want to complete a security course on my phone during a break, so that training fits around my work schedule.*
- AC1: Course Player is fully responsive on mobile viewports.
- AC2: Progress is saved automatically and resumable across devices.

**US-3:** *As a Security Officer, I want to launch a phishing simulation targeted at a specific department, so that I can measure and improve their specific risk profile.*
- AC1: Campaign creation allows targeting by department, role, or individual.
- AC2: Results dashboard shows open/click/report rates in real time.
- AC3: Employees who click receive immediate, non-punitive educational follow-up content.

**US-4:** *As an Organisation Administrator, I want new employees added via CSV import to automatically receive their assigned onboarding training, so that I don't have to manually assign courses.*
- AC1: CSV import validates required fields and reports row-level errors.
- AC2: Course assignment rules apply automatically upon successful import.

**US-5:** *As an Executive, I want a single dashboard showing our organisation's human cyber risk trend over the last quarter, so that I can report it to the board without needing technical interpretation.*
- AC1: Dashboard displays a trended Human Risk Score with plain-language narrative (AI-generated).
- AC2: No technical jargon; drill-down available but not required for the top-level view.

*(Full user story backlog to be maintained in the Phase 9 Development Roadmap; this SRS captures representative coverage across modules.)*

---

## 6. Business Rules

- BR-1: An employee cannot be assigned a course outside their tenant's licensed module tier.
- BR-2: MFA is mandatory (non-optional, non-tenant-configurable) for all roles with administrative or audit privileges.
- BR-3: Policy acknowledgement records are immutable once submitted; corrections require a new versioned acknowledgement, not an edit.
- BR-4: Phishing simulation results shall never be exposed to an employee's direct manager in a way that identifies individual failure without organisation-level opt-in configuration — default is aggregate/department-level visibility only, to protect the "educational, non-punitive" principle established in Phase 1.
- BR-5: A tenant's data (courses, employees, results) is never visible or queryable by another tenant under any application code path.

## 7. Constraints

- Must be built on the confirmed technology stack: Laravel (PHP 8.3), PostgreSQL 16, Blade + Bootstrap 5 (+ Alpine.js where needed), Docker/Nginx, Redis, GitHub Actions CI/CD, Cloudflare.
- Must support deployment on Ubuntu Linux infrastructure.
- v1.0 is single-language (English) and single-region deployment.

## 8. Assumptions

- Tenants will provide their own employee data (no assumed integration with external HR systems in v1.0 beyond CSV import).
- Initial content library (courses, phishing templates) will be produced in parallel with engineering work, not blocking it.
- Regulatory mapping (POPIA, NDPA, etc.) will be handled as configurable compliance framework templates rather than hard-coded logic, to allow expansion to additional jurisdictions later.

---

## Deliverables

- This Software Requirements Specification

## Risks

- BR-4 (non-punitive phishing visibility) may conflict with some customers' expectations of individual accountability — needs explicit configuration options and clear default communicated in sales/onboarding, not just engineering.
- CSV-only employee import (no live HR integration) may be a blocker for larger enterprise prospects — flagged as a fast-follow candidate post-v1.0.
- OWASP ASVS Level 2 target requires independent security assessment budget/time before GA — should be scheduled explicitly in Phase 9 roadmap.

## Next Phase

**Phase 3 — Product Requirements Document:** Full enterprise PRD including executive summary, roadmap, user flows, wireframes, and consolidated functional/non-functional/compliance/security/accessibility/performance requirements with release plan.
