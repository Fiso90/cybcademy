# CybCademy — Master Project Index

**Developed by Solunar Informatics, per the original master prompt's 15-phase development plan.**

This is the top-level map. Start here.

## Phase Documents (design, Phases 1–9)

| Phase | Document |
|---|---|
| 1 — Product Discovery | `CybCademy_Phase1_Product_Discovery.md` |
| 2 — Requirements (SRS) | `CybCademy_Phase2_SRS.md` |
| 3 — Product Requirements (PRD) | `CybCademy_Phase3_PRD.md` |
| 4 — System Architecture | `CybCademy_Phase4_Architecture.md` |
| 5 — Database Design | `CybCademy_Phase5_Database_Design.md` |
| 6 — UI/UX Design Guide | `CybCademy_Phase6_UIUX.md` |
| 7 — Security Architecture | `CybCademy_Phase7_Security_Architecture.md` |
| 8 — API Design | `CybCademy_Phase8_API_Design.md` |
| 9 — Development Plan | `CybCademy_Phase9_Development_Plan.md` |

## Build Phases (10–15) — this repository

| Phase | Summary | Index |
|---|---|---|
| 10 — Backend Development | All 10 epics (E1–E10), ~124 PHP files | `README_PHASE10_INDEX.md` |
| 11 — Frontend Development | All 10 screens, Blade + Bootstrap 5 + Alpine.js | `README_PHASE11_FRONTEND.md` |
| 12 — Testing | 13 test files, coverage report | `README_PHASE12_TEST_PLAN.md` |
| 13 — DevOps | Docker, CI/CD, deployment scripts | `README_PHASE13_DEVOPS.md` |
| 14 — Documentation | 8 guides in `docs/` | `README_PHASE14_DOCUMENTATION.md` |
| 15 — Release | Go-live checklists, honest readiness assessment | `README_PHASE15_RELEASE.md` |

## Beyond the 15 Phases

| Addition | Summary | Index |
|---|---|---|
| Starter Content Library | 3 real courses, gradeable assessments, platform phishing templates | `README_STARTER_CONTENT_LIBRARY.md` |
| Business, Legal, Compliance & Security Documents | 15 documents — Terms of Service, Privacy Policy, DPA, SLA, POPIA/NDPA mapping, Incident Response Plan, and more | `README_ADDITIONAL_BUSINESS_DOCS.md` |

## Beyond the 15 Phases

| Addition | Summary | Index |
|---|---|---|
| Starter Content Library | 3 real courses, gradeable assessments, platform phishing templates | `README_STARTER_CONTENT_LIBRARY.md` |
| Business, Legal, Compliance & Security Documents | 15 documents — Terms of Service, Privacy Policy, DPA, SLA, POPIA/NDPA mapping, Incident Response Plan, and more | `README_ADDITIONAL_BUSINESS_DOCS.md` |
| **Live Environment Verification** | PostgreSQL/Redis/PHP actually installed and run for real; a genuine, production-breaking tenant-isolation bug was found and fixed via live testing against real infrastructure — **the codebase itself was corrected as a result, not just documented** | `README_LIVE_VERIFICATION_REPORT.md` |

## If You Read Nothing Else

**Read `README_LIVE_VERIFICATION_REPORT.md` first**, even ahead of the Phase 15 Release document. It describes a real bug found by actually running this code against live PostgreSQL — `TenantContext::set()`'s original implementation would have made the entire application return zero rows to every user, on every request, after the first internal query, under the project's own confirmed PHP-FPM deployment model. This was found and fixed after 15 phases of careful design and code review that did not catch it, specifically because it only manifests outside the transaction-wrapped conditions the test suite runs under. It's a concrete demonstration of why "designed carefully" and "verified by actually running it" are different claims — and why Phase 15's recommendation against premature GA release remains the right call.

## The Throughline

If there's one idea worth carrying forward from this entire project, it's this: **a small number of business rules (BR-2 MFA, BR-3 policy immutability, BR-4 phishing aggregation) are each enforced in exactly one place in the code**, and every consumer of that rule calls through that one place rather than reimplementing the check. This pattern, established in Epic E1 and followed without exception through Epic E10, the frontend, and the tests, is why a 15-phase, ~290-file project held together as coherently as it did. Any future work on this codebase should preserve that discipline before adding new features on top of it.
