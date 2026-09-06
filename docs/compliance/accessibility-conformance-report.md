# CybCademy — Accessibility Conformance Report

**Format:** Based on the structure of a VPAT (Voluntary Product Accessibility Template), the format most enterprise and public-sector procurement teams expect — Phase 1 explicitly names public sector bodies as a target market, and public procurement in most jurisdictions requires exactly this kind of document.

**⚠️ Status: This report describes design intent, not a verified conformance level.** Phase 6 (UI Design Guide) established WCAG 2.1 AA as a design requirement, and specific measures were built into the design system (see Section 3 below). But Phase 12's Test Plan explicitly states: *"No accessibility test automation... Phase 6's WCAG 2.1 AA commitment is currently verified only by manual review (if at all)."* **This report cannot yet claim conformance — it can only describe what was designed for and flag that verification is outstanding.** Presenting this as a conformance claim without that verification would be inaccurate and could create liability exposure in a procurement context specifically, where these claims are often relied upon contractually.

---

## 1. Product Information

**Product:** CybCademy Web Application
**Report Date:** [DATE — update when actually verified]
**Standard Evaluated Against:** WCAG 2.1, Level AA
**Evaluation Method:** [PLACEHOLDER — must state whether this was automated testing (e.g., axe-core), manual expert review, or user testing with assistive technology; currently none of these have been performed per Phase 12]

## 2. Conformance Summary

| Conformance Level | Status |
|---|---|
| Level A | Not verified |
| Level AA | Not verified |

**This is not "fails to conform" — it is "conformance has not been tested."** These are meaningfully different claims, and this report should not be read as either a pass or a fail until real testing occurs.

## 3. Design-Level Measures (What Was Built With Accessibility in Mind)

These are real, implemented design decisions from Phase 6/11 — listed here as the starting point for verification, not as proof of conformance:

| WCAG Success Criterion (informal grouping) | What Was Designed | Verification Status |
|---|---|---|
| Keyboard navigability, visible focus | `:focus-visible` outlines implemented globally in the design system CSS, never suppressed without replacement (Phase 6 Section 8) | Not independently tested |
| Colour not the sole indicator of meaning | The Risk Ring component and all status badges pair colour with a text label (e.g., "High Risk" text, not just a red dot) — Phase 6 Section 8, Phase 11's `risk-ring.blade.php` | Implemented in code; not independently tested |
| Text contrast | Colour palette (Phase 6 Section 2.1) was chosen with stated intent to meet AA contrast ratios against both Light and Dark mode surfaces | Explicitly flagged as unverified in Phase 6's own Risks section — "Risk-tier colour contrast... must be re-verified once actual Dark Mode surface colours are finalised" |
| Reduced motion | `prefers-reduced-motion` media query implemented globally, disabling animation duration (Phase 11's `app.css`) | Implemented; not independently tested |
| Text resizing / minimum text size | 16px minimum body text size enforced in design tokens (Phase 6 Section 2.2) | Implemented; not independently tested |
| Non-text content (images, icons) | Some ARIA labels exist (e.g., Risk Ring's `role="img"` + `aria-label`) but this was not applied as a checklist across every icon-only button | Gap — icon-only buttons (e.g., sidebar toggle, notification bell in `layouts/app.blade.php`) need a systematic pass, not spot-checking |
| Forms and error identification | Form validation errors are specific (Phase 6 Section 9's interface-voice principle), but a systematic check that every form field has a properly associated `<label>` was not performed | Gap — needs audit |
| Screen reader compatibility (broad) | Not tested with any actual screen reader (NVDA, JAWS, VoiceOver) at any point in this project | Gap — no testing has occurred |

## 4. Known Gaps Requiring Remediation Before Any Conformance Claim

1. No automated accessibility testing (axe-core or equivalent) has ever been run against the built application — this is the single highest-priority next step, since it would surface most of the gaps below systematically rather than one at a time.
2. No testing with actual assistive technology (screen readers, switch devices) has occurred.
3. Icon-only interactive elements need a systematic `aria-label` audit across all ten Phase 11 screens, not just the ones spot-checked during design.
4. Colour contrast ratios need to be measured against the final, as-rendered Dark Mode palette, not just assumed from the token values chosen in Phase 6.
5. Keyboard-only navigation (tab order, no mouse) has not been walked through end-to-end on any screen.

## 5. Recommendation

**Do not present this document to a procurement process as a conformance claim.** Its honest, current use is as a starting checklist for an actual accessibility audit — ideally by someone with disabilities and/or assistive-technology expertise, not solely automated tooling. Once that audit happens, this document should be rewritten with real findings, not design intentions, and the conformance summary in Section 2 updated to reflect actual, tested results (which will likely show partial conformance with specific listed exceptions — that's normal and still procurable in most public-sector contexts, which generally expect a remediation roadmap alongside a report, not perfection).
