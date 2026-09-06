# CybCademy — Phase 10 Code Drop: Epic E6 — Analytics & Dashboards

Fifth slice of Phase 10 (Sprints 11–12 per the Phase 9 plan). This is the first epic that reads across everything shipped so far (E1–E5) rather than introducing new primary data — worth reviewing E1–E5 first if you haven't, since a bug there would now surface here as a wrong number on a dashboard rather than a wrong record in a table.

## What's included

| Layer | Files |
|---|---|
| **Migration** | `human_risk_scores` — time-series, with a database-level CHECK constraint enforcing exactly one of `user_id`/`department_id` is set per row |
| **Model** | `HumanRiskScore` |
| **The scoring engine** | `HumanRiskScoreCalculator` — the platform's single most consequential, customer-facing algorithm |
| **Aggregation** | `DepartmentRiskAggregator` — rolls individual scores up to department level |
| **Scheduled job** | `RecalculateHumanRiskScores` — periodic snapshot, not live-updating on every page load |
| **Dashboard services** | `ComplianceDashboardService` (FR-7.2), `ExecutiveDashboardService` (FR-7.3) |
| **Controller** | `AnalyticsController` |
| **Tests** | `HumanRiskScoreCalculatorTest` — locks in the weighting formula and the neutral-default behaviour as explicit, intentional decisions |

## Design decisions worth knowing about

1. **The score is 0–100 where HIGHER means LOWER risk**, not the more intuitive-sounding "higher score = higher risk." This was a deliberate choice tied to the Risk Ring visual (Phase 6): the ring fills up as risk improves, which reads as encouraging rather than alarming — consistent with BR-4's non-punitive design principle carrying through into the metric itself, not just the phishing module. If this gets relitigated later, it's a product decision with UX consequences, not just a sign flip in code.
2. **A brand-new employee with no history scores 100 (best), not 0 (worst).** Tested explicitly (`test_a_brand_new_employee_with_no_history_scores_100_not_0`). Scoring a day-one hire as "high risk" because they haven't had time to do anything yet would be actively misleading to a Compliance Officer reading the dashboard.
3. **The 40/30/30 weighting (training/assessment/phishing) is documented in the code, not just this README**, specifically because it's the kind of number that's easy to quietly change in one place and forget to update the Phase 3 KPI framing that references it. Any change to these weights should be treated as a product decision, not a code tweak.
4. **Phishing resilience scoring is binary per campaign, not partial credit** — "clicked, but then reported" currently counts as a fully redeemed good outcome, same as never clicking at all. This is called out explicitly in both the calculator's docblock and a dedicated test, specifically so a future change to this logic is a deliberate decision rather than something that silently drifts.
5. **Recalculation is scheduled, not event-driven.** Nothing in E1–E5 triggers an immediate score recalculation on every course completion or phishing click — `RecalculateHumanRiskScores` runs on a cadence (e.g. nightly, scheduling wiring left to Phase 13) and does a full pass. This keeps score calculation off the interactive request path, matching the Phase 2/3 p95 performance targets, at the cost of the score being a snapshot rather than instantaneous — a trade-off worth confirming is acceptable to you, since "why didn't my score update yet" is a plausible support question.
6. **Department scores are an average of individual scores, and org scores are an average of department scores** — not independently re-derived at each level from raw data. This keeps the three dashboard views (employee, department, executive) mutually consistent by construction; a single anomalous employee can't silently distort the org-wide number more than their department's share warrants.

## Known simplifications, called out honestly

- The scheduled cadence for `RecalculateHumanRiskScores` (e.g. nightly via Laravel's scheduler) is not wired up in this excerpt — that's a one-line addition to `app/Console/Kernel.php` in Phase 13, deliberately deferred since scheduling infrastructure belongs with the rest of DevOps setup.
- `ComplianceDashboardService::policyAcknowledgementSummary()` loops per-policy with a separate count query rather than a single aggregated query — acceptable at expected v1.0 scale (tens of policies, not thousands), flagged as a candidate for query consolidation if policy counts grow significantly.
- AI-generated executive narrative text (Phase 3 Section 8.1's "Your organisation's risk trend improved 8% this quarter...") is not implemented here — that's Epic E9 (AI Features), which consumes `ExecutiveDashboardService::orgWideTrend()` as its input once it lands.

## Next steps

Per the Phase 9 sprint plan, next is **Epic E7 (Audit Centre)** — the evidence export functionality, building on the `audit_logs` table and `AuditLogger` write path already in place since Epic E1.
