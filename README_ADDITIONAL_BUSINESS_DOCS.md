# CybCademy — Business, Legal, Compliance & Security Documents

Fifteen documents, added after the original 15-phase development plan concluded, in response to the gap analysis in the previous conversation turn — "what other documents may be needed?"

## Index

| Category | Document | Status |
|---|---|---|
| **Legal** | `docs/legal/terms-of-service.md` | Draft, requires legal review — do not publish |
| | `docs/legal/privacy-policy.md` | Draft, requires legal review — do not publish |
| | `docs/legal/data-processing-agreement.md` | Draft, requires legal review — cannot be finalised until hosting region is decided |
| | `docs/legal/sla.md` | Draft, requires commercial + legal sign-off; several sections blocked on unbuilt features (support process, billing) |
| | `docs/legal/msa-order-form-template.md` | Structural template, needs full legal drafting of MSA body |
| | `docs/legal/sub-processor-list.md` | Placeholder-heavy — three of five vendors not yet selected |
| **Compliance** | `docs/compliance/popia-ndpa-compliance-matrix.md` | Engineering-to-legal handoff artifact, not a certification |
| | `docs/compliance/data-retention-deletion-policy.md` | Flags a real, unresolved tension between immutable records and erasure rights |
| | `docs/compliance/accessibility-conformance-report.md` | Describes design intent only — no accessibility testing has ever been performed |
| **Security** | `docs/security/incident-response-plan.md` | First draft, never exercised via a tabletop drill |
| | `docs/security/vendor-risk-register.md` | Internal-facing counterpart to the Sub-Processor List |
| | `docs/security/security-questionnaire-pack.md` | Ready to use — deliberately written so honest "not yet" answers stay honest |
| **Product** | `docs/product/content-style-guide.md` | Ready to use |
| | `docs/product/bug-tracking-template.md` | Ready to use, cross-references known gaps so they're not re-reported as new |
| **Sales** | `docs/sales/one-pager.md` | Contains placeholder pricing and unconfirmed claims — do not print for a real prospect as-is |
| | `docs/sales/onboarding-playbook.md` | Ready to use |

## A Deliberate Pattern Across All Fifteen

Every legal and compliance document in this batch is marked, explicitly and repeatedly, as a **draft requiring qualified review** — not because that's a boilerplate disclaimer, but because these documents carry real consequences if relied upon incorrectly, and I'm not a lawyer. The technical documents (security questionnaire pack, style guide, bug template, onboarding playbook) don't carry that same caveat, because they're operational guidance, not legal instruments — but they inherit the same honesty discipline the entire project has followed since Phase 1: **every claim points at a real, specific control or explicitly flags a real, specific gap.** None of these documents say "we take security seriously" without saying what that actually means in this specific codebase.

## What This Batch Deliberately Does Not Do

- **It does not fill in the placeholders.** Pricing, hosting region, support SLAs, and payment terms are business decisions Solunar has to make — no amount of document drafting can substitute for them, and pretending otherwise would make these documents actively misleading rather than useful.
- **It does not claim compliance, conformance, or certification anywhere.** The POPIA/NDPA matrix maps controls to requirements; it doesn't say "CybCademy is POPIA compliant." The accessibility report describes design intent; it doesn't claim WCAG AA conformance. This distinction is maintained consistently and deliberately throughout.
- **It does not replace the three hard blockers from Phase 15.** The independent security assessment, the backup restore drill, and (now partially addressed) the content library gap are still the real gates. If anything, this batch makes those gaps more visible in more places (the security questionnaire pack, the accessibility report, and the compliance matrix all independently arrive at "the independent assessment hasn't happened yet") — which is the point. A gap named once can be overlooked; a gap that surfaces consistently across five different documents is harder to lose track of.
