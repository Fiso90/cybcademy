# CybCademy — Troubleshooting Guide

## Login / Authentication

**"Too many login attempts" even though I'm using the right password.**
Rate limiting (5 attempts per IP+email combination, per Phase 7 Section 10) engages after repeated failures — including failures from other people testing the same account, or a browser autofill sending a stale password. Wait 60 seconds, or ask an Administrator to check `AuthService`'s rate limiter state if this persists unexpectedly.

**Stuck on the MFA challenge screen, code says invalid every time.**
Confirm the authenticator app's clock is synced (TOTP codes are time-based and drift if the device's clock is wrong). If MFA was just enabled and no code was ever set up, the account needs an Administrator to reset MFA enrollment.

**A privileged role (Compliance Officer, Security Officer, etc.) gets a 403 on every request.**
This is very likely BR-2's MFA enforcement (`EnsureMfaVerified` middleware) — these roles cannot bypass MFA, by design, even temporarily. Confirm `mfa_enabled` is true on the account and that the current session actually completed the MFA challenge (`session('mfa_verified_at')` — check via `php artisan tinker` if debugging server-side).

## "I can't see [some data] that I should be able to see"

Check role and permission assignment first — this is usually intentional access control, not a bug. Specifically:

- **Phishing simulation results show department totals, not names.** This is BR-4 working as designed for Manager-level roles — Security Officer, Compliance Officer, and Admin roles see individual data. See `docs/api-documentation.md`'s note on this endpoint.
- **A resource "doesn't exist" (404) that you know was created.** If this is a cross-tenant scenario (e.g. testing with two different organisation accounts), a 404 rather than 403 is deliberate — see `CrossTenantHttpAccessTest`'s docblock for why the platform never confirms a resource exists in someone else's tenant.

## AI Features

**"This feature requires a subscription tier upgrade" (HTTP 402).**
Working as intended — `TierGateService` gates AI features by plan. Basic tier has none; Professional adds Quiz Generator, Policy Summariser, Risk Recommendations; Enterprise adds Executive Reports and AI Phishing Template generation. See the Administrator Guide.

**AI feature request fails with an error about email addresses.**
`AiGatewayService`'s PII guardrail rejected the prompt because it detected an email-address pattern. This is deliberately narrow and conservative (see that class's docblock) — if this fires on a false positive, it's worth reporting, since the guardrail is meant to be a backstop, not a routine blocker.

## Phishing Simulation

**A campaign says "sent" but employees report never receiving anything.**
Check the `queue-phishing-sends` container's logs and queue depth — sends are dispatched as queued jobs (`SendPhishingSimulationEmail`), one per recipient, and a stalled or crashed worker for that specific queue would leave the campaign marked "sent" (the launch action itself succeeded) while individual sends are still pending or failing silently in the queue.

**Clicking a simulation link does nothing, or shows a generic error.**
This may be intentional — `PhishingInteractionController::click()` returns the same response whether a tracking token is valid or expired/garbage, specifically so the endpoint doesn't leak which links are "real" simulations. If this is happening for a link that should be active, check the token hasn't exceeded its 30-day cache TTL.

## Course Player / Assessments

**Assessment submission returns a score that seems wrong.**
Grading happens entirely server-side (`AssessmentService::submitAttempt`) — the score returned is authoritative. If you suspect a specific question is mis-scored, check the `question_bank` record's `correct_answer` field directly rather than assuming a frontend display issue; the correct answer is never sent to the client (see `CourseShowHidesCorrectAnswersTest`), so a frontend bug in *displaying* the question can't cause a *grading* discrepancy — those are fully decoupled by design.

**A course with no final assessment never shows as "completed."**
Known, documented gap — `course_assignments.status` currently only transitions to `completed` via assessment submission. A pure-lesson course with no quiz has no completion trigger yet. See `README_PHASE11_FRONTEND.md`'s third slice.

## Audit Export

**Export has been "processing" for more than 5 minutes.**
The job has a hard 300-second timeout matching the FR-8.2 SLA and will fail (not hang) past that — check `audit_exports.status` and `failure_reason` directly, and check the `queue-audit-exports` container's logs. A stuck "processing" status past the timeout window indicates the queue worker itself may be down, not that the job is legitimately still running.

## General Debugging Steps

1. Confirm which container/service is actually implicated (`docker compose ps`) before assuming an application bug — a stopped worker container looks identical to "the feature is broken" from a user's perspective.
2. Check `audit_logs` for the relevant tenant and time window — most user-initiated actions are logged there with before/after state, which is often faster than reproducing the issue.
3. If it's tenant-isolation-adjacent (data from the wrong organisation, or missing data that should be visible), treat it as a security issue first, ordinary bug second — escalate rather than quietly patching, per the Security Architecture document's incident-handling posture.
