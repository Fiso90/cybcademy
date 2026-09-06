# CybCademy — Phase 15: Release

**Product:** CybCademy — Human Cyber Risk Management & Cybersecurity Awareness Platform
**Developed by:** Solunar Informatics
**Document Version:** 1.0
**Date:** 01 August 2026
**Status:** Draft for Approval
**Depends on:** Phase 1–14 (all complete)

---

## 1. Purpose

This document is the final gate before Version 1.0. Its job is not to declare the platform ready — it's to give whoever makes that call a complete, honest list of what's actually done, what's genuinely outstanding, and what open items are or aren't acceptable to ship without. Several items on the checklists below are marked **not complete**, deliberately, because they aren't. A go-live checklist that only ever shows green isn't doing its job.

## 2. Production Checklist

| Item | Status | Notes |
|---|---|---|
| All 10 backend epics (E1–E10) implemented | ✅ Complete | `README_PHASE10_INDEX.md` |
| All 10 frontend screens implemented | ✅ Complete | `README_PHASE11_FRONTEND.md` |
| Multi-tenancy + RLS enforced on every tenant-scoped table | ✅ Complete | Verified by automated test (`TenantIsolationTest`), 20 tables covered |
| MFA enforced for privileged roles | ✅ Complete | `EnsureMfaVerified`, tested at HTTP layer (Phase 12) |
| CI/CD pipeline operational | ✅ Complete | `.github/workflows/ci-cd.yml`, real PostgreSQL service container |
| Deployment/rollback scripts | ✅ Complete | `deploy/deploy.sh`, `deploy/rollback.sh` |
| Database backups (secondary, independent) | ✅ Complete | `deploy/backup.sh` |
| All 8 documentation deliverables | ✅ Complete | `docs/` |
| OpenAPI specification file generated | ❌ Not complete | Referenced by name since Phase 8; never actually produced (flagged in Phase 14) |
| Malware scanning for uploaded files | ❌ Not complete | Config stubbed since Phase 7; no scanner integrated |
| PDF rendering (certificates, audit exports) | ❌ Not complete | Database/queue structure ready; no rendering library selected |
| Payment/billing provider integration | ❌ Not complete | `TierGateService` handles tier consequences; nothing creates/updates real subscriptions from an actual payment event |
| Notification delivery channels (email/push) | ❌ Not complete | Records are created; nothing sends them anywhere a person would see them outside the in-app bell |
| Course completion for lesson-only courses (no assessment) | ❌ Not complete | Flagged since Phase 11; no completion trigger exists |
| Compliance Dashboard department filter data source | ❌ Not complete | Flagged since Phase 11; dropdown has no populating endpoint wired |

**Assessment: not production-ready as-is.** The core platform — multi-tenant training, policy, phishing simulation, incident reporting, analytics, and audit evidence — is functionally complete and tested. The gaps above are real, not cosmetic: no billing integration means the product cannot actually be sold and metered yet; no notification delivery means reminder/assignment emails never reach anyone; no malware scanning is a genuine security gap for a product whose entire pitch is security.

## 3. Go-Live Checklist

| Item | Status |
|---|---|
| Pilot customers identified (Phase 1 Business Case) | Depends on Solunar's sales pipeline — outside this document's scope to verify |
| Closed beta completed with real customer feedback | ❌ Not run — no beta has occurred against this build |
| Content library (courses, phishing templates, policy templates) populated | ⚠️ Starter library added (see `README_STARTER_CONTENT_LIBRARY.md`) — 3 real courses with gradeable assessments and 3 platform phishing templates now exist, per-tenant via `content:seed-starter-library`. This is a genuine improvement from zero, but still far short of the content breadth Phase 1's Business Case envisioned for competing with established players — treat this as "no longer a hard blocker to a pilot launch," not as "content is done." |
| Support process defined (who answers a customer's question) | ❌ Not addressed |
| Pricing finalised and reflected in `TierGateService`'s feature mapping | Partially — the mapping exists and matches the PRD's stated tier structure, but nothing confirms actual go-to-market pricing was finalised against it |

## 4. Security Audit Checklist

| Item | Status |
|---|---|
| OWASP Top 10 mitigations implemented in code | ✅ Complete — Phase 7 Section 2's full mapping |
| Two-layer tenant isolation, independently tested | ✅ Complete |
| MFA, password policy, encryption at rest/in transit | ✅ Complete (encryption at rest depends on the chosen managed PostgreSQL/S3 provider's native support — verify this is actually enabled on whatever provider is selected; this codebase cannot enforce a provider-level setting) |
| Immutable audit logging with privilege-revoked write path | ✅ Complete |
| Independent OWASP ASVS Level 2 assessment by a third party | ❌ Not done — flagged as a risk in every phase from Phase 3 onward; this project's own testing (Phase 12) is not a substitute for this |
| Rate limiting on public/sensitive endpoints | ✅ Complete, tested |
| Security headers (CSP, HSTS, etc.) | ✅ Complete, implemented in Phase 13's Nginx config |
| Malware scanning on uploads | ❌ Not done (repeated from Production Checklist — this belongs on both lists) |

**This checklist cannot be signed off as complete.** The independent third-party assessment is not a formality — it's the actual external validation the PRD committed to delivering, and internal test coverage (however thorough) is not a substitute for it, however much of this document might read that way if skimmed.

## 5. Performance Checklist

| Item | Status |
|---|---|
| p95 dashboard load < 2s | ❌ Never measured — no load-testing tooling exists (flagged in Phase 12) |
| p95 API response < 500ms | ❌ Never measured |
| Audit export < 5 minutes for 5,000 employees | ❌ Never measured at real scale — the job has a hard 300s timeout enforcing the *ceiling*, but nobody has run it against 5,000 real employee records to confirm it actually completes within that ceiling under realistic load |
| Queue throughput under load (phishing sends, exports) | ❌ Never measured |

**None of the performance NFRs specified in Phase 2/3 have been empirically verified.** Every architectural decision made throughout this project (isolated queues, dedicated timeouts, Redis caching) was made *in service of* these targets, but "designed to hit the target" and "measured to hit the target" are different claims, and only the first one is currently true.

## 6. Backup Checklist

| Item | Status |
|---|---|
| Automated daily backups | ✅ Complete (secondary mechanism; primary depends on managed provider) |
| Encryption at rest for backups | ✅ Complete (flagged as needing to move from shared-passphrase GPG to managed KMS for production-grade implementation) |
| Backups stored in separate failure domain | ✅ Complete |
| Restore drill actually performed at least once | ❌ Not done — the backup script has never been proven to produce a restorable backup, only a backup that completes without error |

**This is the single most important unchecked box on this entire document.** A backup process that has run successfully every day but has never been restored is, strictly speaking, unverified. Recommend this be resolved before GA, not deferred to "sometime in the first quarter post-launch" — an untested backup strategy discovered to be broken during an actual incident is the worst possible time to find out.

## 7. Release Notes — Version 1.0 (Draft)

### What's New
CybCademy v1.0 is a complete, multi-tenant Human Cyber Risk Management platform covering:
- Employee onboarding, RBAC, and mandatory MFA for privileged roles
- Course authoring, delivery, server-side-graded assessments, and verifiable certificates
- Versioned policy management with immutable acknowledgement tracking
- Phishing simulation with non-punitive, role-gated results visibility
- Incident reporting with a triage workflow
- The Human Risk Score — individual, department, and organisation-wide, feeding Executive and Compliance dashboards
- An Audit Centre with sub-5-minute evidence export
- Five AI-powered features (Quiz Generator, Policy Summariser, Phishing Template Generator, Risk Recommendations, Executive Narratives), tier-gated by subscription
- Full Docker-based deployment infrastructure and CI/CD pipeline

### Known Limitations (v1.0)
- No live payment/billing integration — subscriptions must be provisioned manually until this is built
- No email/push notification delivery — in-app notifications only
- Course content library is empty at launch — customers must author their own content or Solunar must provide a starter library
- AI Chat Assistant and AI Security Tutor (conversational features) are not included in v1.0
- No native mobile app; responsive web only

### Not Yet Independently Verified
- Performance against stated NFR targets
- Independent third-party security assessment
- Backup restore capability

## 8. Recommendation

**Do not tag this Version 1.0 for a paying-customer GA release yet.** The engineering work across all 15 phases is genuinely complete and, based on this project's own testing, sound. Two items remain genuine hard blockers regardless of the starter content library added after this document was first drafted: the independent security assessment and a real restore drill — neither is something engineering work alone can close, both require an external party or a deliberate, scheduled operational exercise. The content gap has moved from a hard blocker to a "sufficient for a pilot, not for broad GA" state — three real courses exist, but Phase 1's competitive positioning assumed far more breadth than that. Billing integration, notification delivery, and the remaining minor UI gaps can reasonably be sequenced as fast-follow work in the weeks immediately after a soft/pilot launch.

---

## Deliverables

- This Release document (production/go-live/security/performance/backup checklists, draft release notes)

## Assumptions

- Solunar Informatics will treat the three hard blockers identified in Section 8 as genuine gate criteria, not items to be waived under launch-date pressure

## Risks

- The temptation to treat a long, thorough 15-phase design and build process as itself sufficient evidence of readiness is real — thoroughness of process is not the same as verification of outcome, and this document has tried throughout to keep those two things separate rather than letting the former imply the latter.

## Next Phase

None — this is the final phase of the master development plan. Recommend a focused, short "Launch Readiness" effort addressing Section 8's three hard blockers before any customer-facing release, followed by ordinary post-launch iteration.
