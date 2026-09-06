# CybCademy — Phase 10 Code Drop: Epic E10 — Billing & Platform

Eighth and final slice of Phase 10 (per the Phase 9 sprint plan). Closes one real gap flagged explicitly in the Epic E9 README, and delivers the remaining FR-10.x scope: subscription tiers, notifications, and Knowledge Base.

## What's included

| Layer | Files |
|---|---|
| **Migrations** | `subscriptions`, `notifications`, `knowledge_base_articles` (shared/tenant asymmetric RLS, same pattern as `phishing_templates`) |
| **Models** | `Subscription`, `Notification`, `KnowledgeBaseArticle` |
| **The gap-closer** | `TierGateService` — wired directly into `AiController` (Epic E9), gating every one of the five implemented AI endpoints by subscription tier |
| **Services** | `NotificationService` — also the concrete consumer of `PolicyService::usersWithOutstandingAcknowledgement()`, a connection the Epic E3 README explicitly flagged as not yet wired up |
| **Controllers** | `NotificationController`, `KnowledgeBaseController` |
| **Tests** | `TierGateServiceTest` — proves a Basic-tier tenant is blocked from an Enterprise-only AI feature, an Enterprise tenant can use everything, and a tenant with no subscription at all is blocked from everything |

## Design decisions worth knowing about

1. **The tier gate fails closed on missing/inactive subscriptions, but fails open on unmapped features.** No subscription row at all leads to every gated feature being blocked (`test_a_tenant_with_no_active_subscription_cannot_use_any_gated_feature`). But a feature key with no entry in `FEATURE_MINIMUM_TIER` is treated as available on every tier — this is deliberate: it means adding a new gated feature requires an explicit code change (the entry itself), which is the intended friction point for that specific business decision, rather than every new feature silently inheriting a restrictive or permissive default by accident.
2. **This is wired into real call sites, not just built and left unconnected.** All five Epic E9 AI endpoints now call `$this->tierGate->assertTenantCanUse(...)` before doing any AI work — the gap identified in the E9 README is actually closed in this drop, not just acknowledged again.
3. **`NotificationService` is the concrete answer to a question Epic E3 left open.** That epic's README said policy reminder scheduling "belongs to the Notifications module consuming this method, not implemented here" — `sendOutstandingPolicyReminders()` is that consumption, ready to be invoked on a schedule in Phase 13.
4. **Knowledge Base follows the same shared/tenant-scoped pattern as `PhishingTemplate`** (Epic E4) and uses the same escape-hatch convention for authoring/publishing flows that need to bypass the "published only" global scope — consistent patterns across modules rather than three different approaches to "sometimes shared, sometimes tenant-specific" data.

## Known simplifications, called out honestly

- Actual payment processing / billing provider integration (Stripe or similar) is not implemented — `Subscription` rows in this excerpt are assumed to be created/updated by a webhook handler from that provider, which is Phase 13 tooling work, not represented here. This module handles the *consequences* of a subscription's state (tier gating), not the billing transaction itself.
- `TierGateService`'s feature-to-tier mapping is a hardcoded array, not a tenant-configurable or database-driven mapping — acceptable for v1.0 where the tier structure itself is fixed by the platform (Phase 3 Section 5), but would need to move to configuration if pricing tiers become more dynamic.
- Email/push delivery of notifications is not implemented — `NotificationService::notify()` creates the database record (which powers the in-app notification bell); an actual delivery channel (email via the Phase 13 mail provider, push via a future mobile app) would consume these records the same way `GenerateAuditExportPackage` and `SendPhishingSimulationEmail` consume their respective triggers.

## Phase 10 status

This completes all ten epics from the Phase 9 Development Plan (E1–E10). Every epic's README has called out its own honest simplifications and forward references rather than claiming completeness it didn't have — worth a consolidated pass reviewing all ten before Phase 11 (Frontend Development) begins building UI against these endpoints, since Phase 11 will be the first place gaps in this backend (missing delivery channels, unimplemented PDF export, etc.) become directly visible rather than staying implementation details.

## Next steps

Per the master prompt's phase sequence, **Phase 11 — Frontend Development** is next (Blade + Bootstrap 5 + Alpine.js, per the confirmed stack), building the actual screens against the API surface shipped across E1–E10. Alternatively, if you'd rather firm up the backend further first, **Phase 12 (Testing)** could go next to build out the test suite's coverage beyond the targeted tests each epic shipped with its own drop.
