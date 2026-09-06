# CybCademy — Phase 10 Code Drop: Epic E9 — AI Features

Seventh slice of Phase 10. Implements 5 of the 7 AI features from Phase 1/Phase 2 FR-9.x — AI Chat Assistant and AI Security Tutor (both interactive, multi-turn conversational features) are flagged as remaining work rather than rushed into this drop; the five implemented here are the ones with a clear single-request/single-response shape that fits cleanly into the epic's remaining scope.

## What's included

| Layer | Files |
|---|---|
| **The choke point** | `AiGatewayService` — every AI call in the codebase goes through this one class |
| **Feature services** | `QuizGeneratorService` (FR-9.2), `PolicySummariserService` (FR-9.3), `PhishingEmailGeneratorService` (FR-9.4), `ExecutiveNarrativeService` (FR-9.6), `RiskRecommendationService` (FR-9.5) |
| **Controller** | `AiController` |
| **Config** | `config/services.php` excerpt — model identifier is environment-configurable |
| **Tests** | `AiFeaturesTest` — covers the PII guardrail and the "AI-generated quiz content is never auto-attached to a live assessment" invariant |

## Design decisions worth knowing about — read these before wiring in a real API key

1. **`AiGatewayService` is the only class that talks to the AI provider.** Every feature service builds a prompt and calls `generate()`; none of them hold an HTTP client or an API key directly. This is what makes the PII guardrail, usage audit logging, and (future) tier-gating actually reliable — there's exactly one place to get those three things right, not five.
2. **The PII guardrail is deliberately blunt, not comprehensive.** It currently catches email addresses via regex and nothing else — tested explicitly (`test_gateway_rejects_a_prompt_containing_an_email_address...`). This is a defensive backstop, not the primary control. The primary control is each feature service being careful about what it puts in a prompt in the first place — see point 4 below.
3. **AI-generated quiz questions are drafts, never auto-live.** `QuizGeneratorService::generateForCourse()` creates `question_bank` rows but does not attach them to any `assessments` row — that's a separate, explicit action a Trainer must take. This matters because assessment results feed directly into the Human Risk Score (Epic E6); a hallucinated "correct answer" reaching a live assessment unreviewed would corrupt a metric executives see, not just annoy one employee. Tested explicitly.
4. **Executive narrative and risk recommendations only ever see aggregated data.** `ExecutiveNarrativeService` and `RiskRecommendationService` both take their input from `ExecutiveDashboardService` — the same department-level aggregation service from Epic E6 — never from anything at individual-employee granularity. This extends BR-4's "aggregate by default" principle from the Phishing module (where it's a written rule) into the AI module (where it's an architectural constraint instead, since there's no equivalent named business rule for AI features specifically — worth considering whether there should be one, given how much sensitive-data-handling judgment these five services collectively exercise).
5. **AI-generated phishing templates are tenant-scoped, not shared platform templates.** They use `PhishingTemplate`'s nullable-`tenant_id` design from Epic E4 with a real tenant_id set — a template generated for one tenant's industry context doesn't leak into another tenant's simulation library.
6. **Every AI call is logged via the same `AuditLogger` used everywhere else** (`ai.{feature}.generated`, with token counts) — both for cost visibility and because "which AI feature touched what, when" is a reasonable question for a compliance product's own audit story to be able to answer about itself.

## Known simplifications, called out honestly

- **AI Security Tutor and AI Chat Assistant are not implemented in this drop.** Both are genuinely different in shape from the other five (multi-turn, stateful conversation vs. single-request generation) and deserve their own conversation-history/session-management design rather than being forced into the same pattern as the other five features. Flagged as remaining E9 scope, not silently dropped.
- **`PolicySummariserService` assumes plain text is already extractable from the stored policy file** — actual PDF/DOCX text extraction is a Phase 13 tooling detail not implemented here.
- **The `assertNoObviousPiiLeak` check is narrow by design** (see point 2) — if you want stronger guarantees before going to production with real tenant data flowing through these prompts, that's worth a dedicated security review in Phase 12, not something to assume this regex handles.
- **Tier-gating (Basic/Professional/Enterprise per Phase 3 Section 5) is mentioned in `AiGatewayService`'s docblock as an intended responsibility but not actually implemented in this excerpt** — currently any authenticated user with the right role can call these endpoints regardless of their tenant's subscription tier. This is a real gap, not a nuance, if AI features are meant to be a paid add-on per the PRD's revenue model (Phase 3 Section 9) — worth prioritising before this goes live commercially.

## Next steps

Per the Phase 9 sprint plan, **Epic E10 (Billing & Platform)** is next — subscription tiers, seat billing, notifications, knowledge base. Worth noting E10 is also where the tier-gating gap flagged above would actually get closed, since that's where subscription tier enforcement infrastructure belongs.
