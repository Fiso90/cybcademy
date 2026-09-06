# CybCademy — Customer Onboarding Playbook

For whoever owns Customer Success/Implementation — walks a new customer through go-live, cross-referencing the actual in-app flow (built in Epic E1/Phase 3's User Flow 8.1) rather than describing a hypothetical process.

---

## Pre-Kickoff Checklist (Before the Customer's First Session)

- [ ] Tenant provisioned (Organisation record created)
- [ ] Subscription tier confirmed and `TierGateService`'s feature mapping matches what was sold (Epic E10) — worth a quick sanity check, since a tier mismatch here is the kind of thing that surfaces awkwardly mid-demo
- [ ] At least one Organisation Administrator account created, MFA-ready (they'll need to complete MFA enrolment during kickoff — cannot be skipped, per BR-2)
- [ ] Starter content library seeded (`php artisan content:seed-starter-library {tenant}`) — **do this before kickoff, not during it**; walking a customer through an empty platform undercuts the whole pitch

## Kickoff Session (Recommend 60–90 minutes)

1. **Admin logs in, completes MFA setup.** This is often the first friction point a new customer hits — walk through it deliberately rather than rushing past it, and explain *why* it's mandatory for their role (this isn't negotiable per platform design, so set the expectation clearly rather than let them discover it's not configurable later).
2. **Import employees.** CSV template walkthrough — flag known limitation clearly: no live HR system integration in this release, so this is a manual process for now, and periodic re-imports (or manual additions) are how the roster stays current.
3. **Review the starter course library** with the Administrator — this is a starting point, not a finished library; set the expectation that they'll likely want to author additional content over time (point them to the Content Style Guide).
4. **Configure onboarding defaults** — which starter courses are marked as onboarding defaults for their organisation, so new hires get assigned automatically going forward.
5. **Walk through the Compliance Dashboard and Audit Centre** — this is usually the feature that matters most to whoever owns the compliance relationship on the customer side; make sure they specifically see the evidence export flow working end-to-end during the session, not just described.

## First 30 Days — Recommended Cadence

| Week | Focus |
|---|---|
| 1 | Employee roster fully imported; onboarding course assignment confirmed working for a real new hire if one exists during this window |
| 2 | First policy uploaded and published; Administrator walks a few employees through acknowledgement to confirm the flow feels right to them |
| 3 | First phishing simulation campaign — **recommend starting with an easier, more obviously-fake template** (see the Content Style Guide's difficulty-variance guidance) rather than the hardest one, to build early confidence rather than an early wave of confused employee questions |
| 4 | Review Human Risk Score data with the customer — note explicitly that scores are calculated on a nightly schedule, not instantly, so don't expect same-day movement after a single course completion |

## Setting Expectations Honestly

A few things worth saying plainly during onboarding, rather than letting a customer discover them and feel misled:

- **Phishing results are aggregated for managers by default** — if the customer's leadership expects to see individual employee results, this is a conversation to have explicitly during kickoff, not something they should discover later and feel surprised by. Explain the design rationale (Phase 1/BR-4's non-punitive philosophy) — most customers, once it's explained, agree with the reasoning.
- **A course with no final assessment currently has no way to mark itself "completed"** in an employee's record (a known platform gap) — if the customer's compliance reporting depends on 100% completion tracking, recommend every course include at least a short assessment for now.
- **Notification delivery is in-app only right now** — no email reminders yet. If the customer's culture depends on email nudges to drive completion, set this expectation early rather than let low completion rates in week 3 become an unexplained surprise.

## Escalation

If onboarding surfaces a genuine product gap (not just a known, documented one from the list above), route it through the Bug Tracking process — Customer Success should not be the only place that knowledge lives.
