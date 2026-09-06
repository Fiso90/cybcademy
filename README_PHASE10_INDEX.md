# CybCademy — Phase 10 Backend: Complete Index (Epics E1–E10)

All ten epics from the Phase 9 Development Plan are now implemented. This file is the map — each epic has its own detailed README; this one is for orientation and for the handful of things that only make sense viewed across the whole backend at once.

## Epic-by-epic

| Epic | Module | README |
|---|---|---|
| E1 | Platform Foundation (tenancy, RLS, auth, MFA, RBAC, audit logging) | `README_E1_FOUNDATION.md` |
| E2 | Learning Management (courses, assessments, certificates) | `README_E2_LEARNING_MANAGEMENT.md` |
| E3 | Policy Management | `README_E3_E5_POLICY_AND_INCIDENTS.md` |
| E4 | Phishing Simulation | `README_E4_PHISHING_SIMULATION.md` |
| E5 | Incident Reporting | `README_E3_E5_POLICY_AND_INCIDENTS.md` |
| E6 | Analytics & Dashboards (Human Risk Score) | `README_E6_ANALYTICS_DASHBOARDS.md` |
| E7 | Audit Centre | `README_E7_AUDIT_CENTRE.md` |
| E9 | AI Features | `README_E9_AI_FEATURES.md` |
| E10 | Billing & Platform | `README_E10_BILLING_PLATFORM.md` |

**E8 (API & Integrations)** has no separate README because, as noted when it came up, its scope was absorbed incrementally into every other epic's controller/route work — each epic shipped its own slice of the Phase 8 API spec directly. What remains of E8's original scope is formalising `docs/openapi.yaml` as a generated artifact and any webhook/integration work beyond what's here — genuinely deferred, not done.

## Threads that run across every epic

A few decisions from Epic E1 turned out to matter in every subsequent epic, worth seeing listed together:

1. **Two-layer tenant isolation** (`BelongsToTenant` trait + PostgreSQL RLS), tested independently in `tests/Security/TenantIsolationTest.php`, which now covers every tenant-scoped table across all ten epics via its hand-maintained `tenantScopedTables()` list.
2. **One audit-log write path** (`App\Support\AuditLogger`), used by every epic from E1 through E10 — there is still, deliberately, no writable Eloquent model for `audit_logs` anywhere in the codebase.
3. **Thin controllers, business logic in Services** — held consistently from `AuthController`/`EmployeeController` in E1 through `AiController`/`KnowledgeBaseController` in E10.
4. **The "shared platform data, nullable tenant_id" pattern**, used three times (`PhishingTemplate` in E4, `KnowledgeBaseArticle` in E10) with the same asymmetric RLS policy shape each time — worth extracting into a shared trait/base class if a fourth use case appears, rather than copy-pasting a fourth time.
5. **Business rules enforced in one place, not many** — BR-2 (MFA) in `EnsureMfaVerified`, BR-3 (policy immutability) in `PolicyAcknowledgementService`, BR-4 (phishing aggregation) in `PhishingResultsRepository`. This pattern is the single most repeated architectural choice across the whole backend, and it's the one most worth protecting in code review as more epics/features get added post-v1.0.

## Honestly incomplete, tracked in one place

Pulling every epic's "known simplifications" section together, so nothing gets lost before Phase 11/12/13:

- **No composer.json / framework bootstrap** — this code assumes an existing Laravel 11 skeleton (E1).
- **No Blade views** — Phase 11's job entirely (E1).
- **PDF certificate rendering, PDF audit export** — query/record structure ready, rendering library not chosen (E2, E7).
- **Malware scanning for file uploads** — flagged in Phase 7, not yet wired to a concrete tool (E3, referenced from Phase 7).
- **`courses.is_onboarding_default` column** — referenced by E2's onboarding listener, not yet added; depends on E10's Settings scope, which itself wasn't fully built out (E2).
- **AI Security Tutor, AI Chat Assistant** — the two multi-turn AI features, deliberately not forced into E9's single-request pattern (E9).
- **AI tier-gating** — flagged as missing in E9, closed in E10.
- **Payment/billing provider integration** — E10 handles subscription *consequences*, not the transaction itself.
- **Notification delivery channels** (email/push) — E10 creates records; delivery is unimplemented.
- **Scheduled job wiring** (`RecalculateHumanRiskScores`, `sendOutstandingPolicyReminders`) — the jobs exist; cron/scheduler registration is Phase 13.

## Suggested next step

Given the volume shipped, a consolidated review pass (even a light one) before Phase 11 starts building screens against this API surface would catch more than reviewing it screen-by-screen after the fact — Phase 11 will be the first point several of the "honestly incomplete" items above become visible as missing functionality rather than staying backend implementation details.
