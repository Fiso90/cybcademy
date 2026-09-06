# CybCademy — Bug / Defect Tracking Template

Phase 12's Test Plan noted explicitly: "no defects have been logged because no exploratory/manual testing pass has occurred against the built system as a whole." This is the template for when that changes — whether via an actual issue tracker (GitHub Issues, Jira, Linear) or, until one is configured, a shared log using this structure.

---

## Defect Report Template

```
Title: [Short, specific summary - "Compliance dashboard department filter returns
       no options" not "dashboard broken"]

Severity: [ ] Critical  [ ] High  [ ] Medium  [ ] Low
          (Use the same severity definitions as the Incident Response Plan's
          classification, Section 1, for consistency - a Critical defect and
          a SEV-1 incident should mean the same thing across both documents.)

Environment: [ ] Production  [ ] Staging  [ ] Local dev
Epic/Module:  [e.g., E4 - Phishing Simulation]
Reported by:  [Name]
Date:         [Date]

Steps to Reproduce:
1.
2.
3.

Expected Behaviour:

Actual Behaviour:

Is this a known/documented gap?
[ ] No - genuinely new
[ ] Yes - already flagged in: [link to the specific README/document section]
    (Check the relevant epic's README and the Phase 15 Release document's
    checklists first - several "bugs" a new tester finds may already be
    documented, intentional gaps rather than undiscovered defects. Confirming
    this saves duplicate investigation.)

Screenshots/Logs:

Suggested Priority: [Triager fills in - severity alone doesn't set priority;
                     a Low-severity bug affecting every user may outrank a
                     Critical-severity bug affecting one edge case]
```

## Triage Guidance

When triaging a new report, check it against these existing "known, documented, not-yet-fixed" gaps before treating it as new — this list should be kept current as gaps are closed:

| Known Gap | Where Documented |
|---|---|
| Lesson-only courses (no assessment) never show as "completed" | `README_PHASE11_FRONTEND.md`, third slice |
| Compliance Dashboard department filter has no data source | `README_PHASE11_FRONTEND.md`, third slice |
| No email/push notification delivery | `README_PHASE10_INDEX.md`, `README_E10_BILLING_PLATFORM.md` |
| No malware scanning on file uploads | Security Architecture Document, Section 14; every epic touching uploads since |
| `human_risk_scores` / `audit_logs` unbounded growth, no partitioning | Database Design Document, Risks |
| No payment/billing provider integration | `README_PHASE15_RELEASE.md` |
| OpenAPI spec file never generated | `README_PHASE14_DOCUMENTATION.md` |

**When a report turns out to be a known gap:** don't close it as "not a bug" — link it to the tracking issue and consider whether its priority should change given a real user hit it (a gap that was theoretical in a design document becomes more urgent once someone actually tripped over it in practice).

## Severity vs. Priority

- **Severity** = how bad is this if it happens (technical/business impact)
- **Priority** = how soon should we fix it (depends on severity, but also frequency, workaround availability, and whether it blocks something else)

A Critical-severity bug in a feature nobody uses yet might be lower priority than a Medium-severity bug in the login flow every single user hits daily. Don't conflate the two fields.
