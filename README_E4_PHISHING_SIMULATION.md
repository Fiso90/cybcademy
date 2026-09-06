# CybCademy — Phase 10 Code Drop: Epic E4 — Phishing Simulation

Fourth slice of Phase 10 (Sprints 8–9 per the Phase 9 plan). Given its own dedicated drop, as flagged previously — this is the module where BR-4 (non-punitive, aggregation-by-default results) concentrates, and it deserved to not be rushed alongside something else.

## What's included

| Layer | Files |
|---|---|
| **Migrations** | `phishing_templates` (nullable `tenant_id` for shared platform templates, custom RLS policy), `phishing_campaigns`, `phishing_results` |
| **Models** | `PhishingTemplate` (custom global scope, not `BelongsToTenant`), `PhishingCampaign`, `PhishingResult` |
| **The BR-4 enforcement point** | `PhishingResultsRepository` — the single, deliberate place role-based aggregation-vs-individual visibility is decided |
| **Services** | `PhishingCampaignService` (create/launch/target resolution), `PhishingInteractionService` (click/report tracking, FR-5.3/5.4) |
| **Job** | `SendPhishingSimulationEmail` — queued, one per recipient, on an isolated `phishing-sends` queue |
| **Controllers** | `PhishingCampaignController` (authenticated management), `PhishingInteractionController` (public tracking links) |
| **Routes** | Authenticated (`app/Modules/PhishingSimulation/routes.php`) and public (`public_routes.php`), registered separately and deliberately |
| **Tests** | `PhishingResultsBr4EnforcementTest` — proves a Manager gets aggregated data with no `user_id` anywhere in the result set, while a Security Officer gets full individual data |

## Design decisions worth knowing about

1. **BR-4 lives in exactly one place: `PhishingResultsRepository`.** Not the controller, not the model, not each dashboard that happens to want this data. `resultsForRequester()` is the method every consumer — API, Executive Dashboard (Epic E6), any future export — should call, and it decides individual-vs-aggregate internally based on the requester's role. The controller (`PhishingCampaignController::results()`) deliberately contains zero role-branching logic itself, specifically so a future developer adding a new consumer of this data can't accidentally reimplement the decision incorrectly.
2. **Tracking links carry an opaque token, never a raw campaign/user ID.** `SendPhishingSimulationEmail` generates a random UUID token and stores the campaign/user mapping server-side (cached, 30-day TTL) rather than putting `?campaign_id=X&user_id=Y` in the URL — a real phishing email doesn't do that, so neither should the simulation, or it stops being a realistic test.
3. **Invalid and valid tracking tokens produce indistinguishable responses.** `PhishingInteractionController::click()` redirects to the same educational page whether the token was real or garbage — so nobody probing the endpoint can distinguish "this is a live simulation link" from "this link is dead," which would otherwise leak information about active campaigns.
4. **Report and Click share one entry point conceptually but branch on outcome.** `recordReport()` returns whether the token was a genuine simulation; if not, the controller routes to Epic E5's incident flow instead — meaning an employee never has to decide "is this simulation or real" before reporting, they just click Report and the system sorts it out (matching the Phase 3 principle that reporting should always be one action).
5. **Campaign sends are queued per-recipient on an isolated queue** (`phishing-sends`), directly addressing the Phase 7 Section 10 risk (a compromised account being used to spam mass sends) — throughput is bounded by queue worker configuration, not by how fast the launch endpoint can loop.

## Known simplifications, called out honestly

- `PhishingTemplate`'s RLS policy is asymmetric (readable when `tenant_id IS NULL` OR matches; writable only with a real `tenant_id`) — this means platform-wide template seeding must happen through a privileged connection that bypasses RLS (the same break-glass pattern referenced in Phase 7 Section 7), not through the ordinary application user flow. Worth remembering when Phase 13 sets up the initial template seed data.
- Actual email delivery (Mailable class, transactional provider integration) is not implemented in this excerpt — `SendPhishingSimulationEmail` handles tracking-token generation and result recording, which is the part with real business-rule content; wiring an actual send provider is a Phase 13 tooling decision.

## Next steps

Per the Phase 9 sprint plan, **Epic E6 (Analytics & Dashboards)** is next — this is where the Human Risk Score engine and the Compliance/Executive Dashboards get built, and it's the first epic that reads data from every module shipped so far (E1–E5), so it's worth confirming all the prior epics look right before building on top of them.
