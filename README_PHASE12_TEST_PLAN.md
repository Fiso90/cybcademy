# CybCademy — Phase 12: Test Plan & Coverage Report

**Product:** CybCademy — Human Cyber Risk Management & Cybersecurity Awareness Platform
**Developed by:** Solunar Informatics
**Document Version:** 1.0
**Date:** 01 August 2026
**Status:** Draft for Approval
**Depends on:** Phase 1–11 (all complete)

---

## 1. Testing Philosophy

Every Phase 10 epic shipped its own targeted tests alongside its code, following a deliberate pattern established from Epic E1 onward: **test the business rule, not the framework**. Rather than generic CRUD tests, each epic's test suite proves the specific thing that would be expensive to get wrong silently — BR-2's MFA mandate, BR-3's acknowledgement immutability, BR-4's phishing-results aggregation, the Human Risk Score's neutral-default and weighting behaviour, and so on.

Phase 12's job is to fill the layer those epic-by-epic tests didn't reach: **system-level tests that exercise the full HTTP request pipeline** (middleware, session, routing) rather than calling service methods directly, and a **consolidated view of what's covered and what isn't**, so nothing is assumed tested just because a nearby unit test exists.

## 2. Test Suite Inventory (as of end of Phase 11)

| Test File | Epic | What it proves |
|---|---|---|
| `Security/TenantIsolationTest.php` | E1, extended every epic | Application-layer scope AND database RLS both independently prevent cross-tenant reads; every tenant-scoped table has an RLS policy (hand-maintained list, currently 20 tables) |
| `Feature/LMS/AssessmentGradingTest.php` | E2 | Grading correctness; certificate issuance is idempotent on re-passing |
| `Feature/Policy/PolicyAcknowledgementImmutabilityTest.php` | E3 | BR-3: duplicate acknowledgement rejected, not overwritten; correction requires a new policy version |
| `Feature/IncidentReporting/IncidentStatusTransitionTest.php` | E5 | Status state machine rejects invalid transitions (open→closed direct) |
| `Feature/PhishingSimulation/PhishingResultsBr4EnforcementTest.php` | E4 | BR-4: Manager role gets aggregated results only; Security Officer gets individual results |
| `Feature/Analytics/HumanRiskScoreCalculatorTest.php` | E6 | Neutral-default (100, not 0) for new employees; documented 40/30/30 weighting; binary phishing-resilience scoring |
| `Feature/AuditCentre/AuditExportTest.php` | E7 | Export request and completion both write to `audit_logs`; record count captured |
| `Feature/AI/AiFeaturesTest.php` | E9 | PII guardrail rejects prompts with email addresses; AI-generated quiz questions never auto-attach to a live assessment |
| `Feature/Billing/TierGateServiceTest.php` | E10 | Tier gate fails closed on missing subscription, correctly gates by tier rank |
| `Feature/LMS/CourseShowHidesCorrectAnswersTest.php` | Phase 11 fix | Correct answers never appear in the Course Player's API response (the security fix documented in Phase 11's README) |
| `Feature/Auth/MfaEnforcementTest.php` | **New, this phase** | BR-2 actually blocks unverified privileged roles at the HTTP layer, not just in isolated service logic |
| `Feature/Auth/LoginRateLimitTest.php` | **New, this phase** | Login rate limiting engages after 5 failed attempts at the real `/login` route |
| `Security/CrossTenantHttpAccessTest.php` | **New, this phase** | A fully authenticated Tenant A request cannot fetch a Tenant B resource by ID via the real API route (404, not 403 — deliberate information-disclosure choice, documented in the test) |

**13 test files, spanning unit-level business logic (most epic tests), system-level HTTP pipeline tests (the three new ones), and security-specific tests (tenant isolation, cross-tenant access, PII guardrail).**

## 3. Coverage by Category

| Category | Status |
|---|---|
| **Unit tests** | Strong — every business rule (BR-2 through BR-4) and the Human Risk Score algorithm have dedicated tests |
| **Integration tests** | Strong — most epic tests exercise Controller→Service→Repository→Model together via `RefreshDatabase`, not mocked layers |
| **System tests** | Partial — three new tests this phase cover login, MFA, and cross-tenant HTTP access; **broader system-level coverage (full user journeys across multiple requests, e.g. onboard→assign→complete→certificate) is not yet built** |
| **Security tests** | Strong on tenant isolation and BR-4; **OWASP Top 10 items beyond access control (A02 encryption, A03 injection, A07 auth failures beyond rate-limiting) have no dedicated automated tests yet** — the Phase 7 mitigations are implemented in code (parameterised queries via Eloquent, Argon2id hashing) but not independently verified by tests that would catch a regression |
| **Performance tests** | **Not built.** Phase 2/3 NFRs specify p95 targets (dashboard <2s, API <500ms, audit export <5min); no load-testing tooling or test suite exists yet to verify these against real numbers |
| **Accessibility tests** | **Not built.** Phase 6's WCAG 2.1 AA requirement has no automated test coverage (e.g. axe-core integration against the Blade views) |

## 4. Gaps and Recommended Next Actions

Being direct about what Phase 12 does *not* close, rather than implying broader coverage than exists:

1. **No automated performance/load testing exists.** The FR-8.2 5-minute export SLA and the p95 latency NFRs are architectural intentions (dedicated queues, timeout constraints) but have never been measured against real load. Recommend a dedicated load-testing pass (k6, Locust, or similar) before GA, sized against the "up to 5,000 employees" scale referenced throughout the design docs.
2. **No accessibility test automation.** Phase 6's WCAG 2.1 AA commitment is currently verified only by manual review (if at all). Recommend axe-core or similar integrated into CI against the built Blade views before Phase 15's go-live checklist.
3. **The independent OWASP ASVS Level 2 assessment** (flagged as a risk since Phase 3 Section 17 and reiterated in Phase 7's Assumptions) has not happened — this test suite is Solunar's own internal testing, not the independent third-party assessment the PRD commits to.
4. **Full user-journey system tests** (multi-request flows spanning several epics — e.g., an employee's entire lifecycle from CSV import through onboarding course assignment, completion, certificate issuance, and a phishing simulation result all in one continuous test) don't exist yet. Current tests are epic-scoped; a handful of true end-to-end journey tests would catch integration issues the epic-scoped tests structurally can't see.
5. **No dedicated bug tracking artifact exists yet** — no defects have been logged because no exploratory/manual testing pass has occurred against the built system as a whole; this document is a coverage report, not a bug report, because there's nothing to report yet at this stage of the process.

## 5. Test Execution

- Test runner: PHPUnit (per confirmed stack, Phase 1's Technology Stack section)
- Database: `RefreshDatabase` trait, run against a real PostgreSQL test database (not SQLite) so RLS policies — which are PostgreSQL-specific — actually execute during tests rather than being silently skipped by a different database engine
- CI integration: GitHub Actions (Phase 4 Section 11's deployment architecture) — pipeline wiring to actually run this suite on every PR is a Phase 13 DevOps task, not yet configured

---

## Deliverables

- This Test Plan & Coverage Report
- Three new system-level test files closing HTTP-pipeline gaps identified during this review (`MfaEnforcementTest`, `LoginRateLimitTest`, `CrossTenantHttpAccessTest`)

## Assumptions

- The test database used in CI will be PostgreSQL, matching production, specifically because RLS policy behaviour cannot be validated against SQLite or another engine
- Load and accessibility testing tooling selection is deferred to whoever owns Phase 13, consistent with how other tooling decisions (malware scanning, PDF rendering) have been deferred throughout this project

## Risks

- **Testing against PostgreSQL RLS in CI requires a real Postgres service container**, not the lighter-weight SQLite-in-memory setup many Laravel projects default to for speed — this will make CI slower and requires deliberate GitHub Actions service configuration in Phase 13; if that configuration is skipped in favour of a faster SQLite-based CI setup, the RLS-dependent tests would silently stop being meaningful (they'd pass against a database engine that doesn't even support the feature being tested).
- The gap between "13 targeted tests covering specific business rules" and "comprehensive test coverage" is real and should not be conflated — this suite catches regressions in the rules it knows to check, not unknown-unknowns. The Phase 15 go-live checklist should not treat this test suite's green status as sufficient sign-off on its own.

## Next Phase

**Phase 13 — DevOps:** Docker, Docker Compose, GitHub Actions (including the CI wiring to actually run this Phase 12 test suite, with a real PostgreSQL service container per the Risk above), Apache/Nginx configuration, SSL, environment variables, deployment scripts, rollback strategy, monitoring, logging, backups.
