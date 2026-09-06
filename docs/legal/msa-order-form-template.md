# Master Services Agreement (MSA) and Order Form Template

**⚠️ DRAFT FOR LEGAL REVIEW.** This is a structural template showing how CybCademy's commercial documents fit together, with the substantive terms deliberately left in the standalone Terms of Service, DPA, and SLA documents (avoiding duplicating the same clause in two places, which is a common source of contract inconsistency). Complete legal drafting of the MSA body itself is still required.

---

## Document Architecture

Rather than one long contract, CybCademy's commercial paper is structured as a stack, matching Phase 3's tiered subscription model and the reality that different customers will need different specifics filled into the same core relationship:

```
Master Services Agreement (MSA)
 ├── incorporates by reference → Terms of Service
 ├── incorporates by reference → Data Processing Agreement
 ├── incorporates by reference → Service Level Agreement
 └── governs → Order Form(s) [one per subscription purchase/renewal/expansion]
```

The MSA is signed once per customer relationship. Order Forms are signed per transaction (initial purchase, renewal, tier upgrade, seat expansion) and reference back to the MSA's terms rather than re-negotiating them each time.

## MSA — Structural Outline

1. **Parties and Effective Date**
2. **Incorporation of Terms** — "This MSA incorporates the Terms of Service, DPA, and SLA in effect at the time of each Order Form, as may be updated by Solunar with notice per the Terms of Service's amendment provisions."
3. **Order Forms** — how they're executed, what they must contain (subscription tier, seat count, term, fees, billing cycle) to be valid
4. **Precedence** — [PLACEHOLDER: standard clause establishing which document controls in case of conflict — commonly Order Form > MSA > incorporated Terms, but this needs counsel's confirmation given Solunar's specific structure]
5. **Term of the MSA** — [PLACEHOLDER: typically continues as long as any Order Form is active, plus a tail period]
6. **Signature blocks**

## Order Form Template

```
CybCademy Order Form

Customer:              [Legal entity name]
Effective Date:        [Date]
Subscription Term:     [12 months / 24 months / etc.]
Billing Cycle:         [ ] Monthly  [ ] Annual

Subscription Tier:     [ ] Basic  [ ] Professional  [ ] Enterprise
Licensed Seats:        [Number] active employees
                        (Per-employee subscription per Phase 3's Revenue
                        Model — confirm seat-counting methodology matches
                        actual billing implementation once built, per the
                        gap flagged in Phase 15's Release document)

Add-on Modules:        [ ] Advanced AI Features (if not included in tier)
                        [ ] [Other add-ons per Phase 3 Section 9]

Fees:                  [Amount] per [seat/month/year]
                        [PLACEHOLDER: currency — given the African market
                        focus from Phase 1, confirm local payment method
                        support before this is usable, since Phase 1's
                        Competitive Analysis specifically flagged
                        "local payment methods" as a differentiator
                        global competitors lack — an Order Form template
                        that only supports one currency/payment rail
                        would undercut that positioning]

Payment Terms:         [PLACEHOLDER — Net 30, etc.]

This Order Form is governed by and incorporates the Master Services
Agreement, Terms of Service, Data Processing Agreement, and Service
Level Agreement in effect between the parties.

Customer signature: _______________  Date: _______
Solunar signature:  _______________  Date: _______
```

## What This Template Deliberately Does Not Include

- Substantive liability, warranty, or IP terms — these live in the Terms of Service, not duplicated here, specifically to avoid two documents making conflicting promises about the same thing.
- Pricing figures — Phase 3's Revenue Model describes the tier structure conceptually but actual go-to-market pricing was never finalised anywhere in this project; that's a business decision, not something this document can draft on Solunar's behalf.

---

**Before this document is used with any real customer:**
1. The full MSA body (Sections 4-6 above) needs actual legal drafting, not the outline provided here.
2. Payment terms and currency support are blocked on the billing integration gap flagged in Phase 15 — this template describes the paperwork, not a working payment flow.
3. Have counsel confirm the precedence clause (Section 4) actually achieves the intended effect under the governing law eventually chosen for the Terms of Service.
