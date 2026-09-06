# CybCademy Terms of Service

**⚠️ DRAFT FOR LEGAL REVIEW — DO NOT PUBLISH OR RELY ON AS-IS.** This is a professional-quality starting template, not a finished legal instrument. It has not been reviewed by a lawyer, has not been checked against the specific jurisdictions Solunar Informatics will operate in (South Africa, Nigeria, and other target markets per Phase 1's Business Case each have their own contract-law and consumer-protection requirements), and contains bracketed placeholders that must be completed before use. Do not present this to a customer as binding.

**Last Updated:** [DATE] · **Version:** 1.0-draft

---

## 1. Agreement to Terms

These Terms of Service ("Terms") govern access to and use of the CybCademy platform ("Service"), provided by Solunar Informatics ("Solunar," "we," "us"). By creating an account or using the Service, the organisation you represent ("Customer," "you") agrees to these Terms. If you are entering into these Terms on behalf of an organisation, you represent that you have authority to bind that organisation.

## 2. The Service

CybCademy is a multi-tenant Human Cyber Risk Management platform providing security awareness training, phishing simulation, policy management, incident reporting, and related analytics, as further described in the Documentation. We may modify features over time; we will not materially reduce core functionality during a paid subscription term without notice.

## 3. Accounts and Access

- Customer is responsible for all activity under its tenant account, including actions taken by its Users.
- Customer must promptly deactivate accounts for employees who leave the organisation (the Service provides tooling for this — see the Administrator Guide).
- Multi-factor authentication is mandatory for certain privileged roles and cannot be disabled by Customer — this is a platform-level security control, not a configurable setting, and Customer acknowledges this as a condition of use.
- [PLACEHOLDER: minimum age requirements for individual Users, if applicable in target jurisdictions]

## 4. Subscription, Fees, and Payment

- Fees are as set out in the applicable Order Form.
- [PLACEHOLDER: payment terms, currency, late payment consequences, auto-renewal terms — Solunar's actual billing provider integration (flagged as not yet built in Phase 15's Release document) will determine some of the mechanics here]
- Subscription tier (Basic/Professional/Enterprise) determines available features per the published feature matrix; downgrading may result in loss of access to tier-specific data views (e.g., AI-generated reports) without loss of underlying data.

## 5. Customer Data

- Customer retains all ownership rights in data it or its Users submit to the Service ("Customer Data"), including employee records, training results, and phishing simulation results.
- Solunar will not use Customer Data to train AI models outside the scope of providing the Service to that Customer, and will not sell Customer Data. [PLACEHOLDER: confirm this against the actual data flow to the AI provider (Anthropic) — see the Data Processing Agreement and Sub-Processor List for the technical detail this clause must accurately reflect]
- Customer is responsible for the accuracy of data it imports (e.g., via CSV employee import) and for having a lawful basis to process its employees' personal data through the Service.

## 6. Phishing Simulation — Specific Terms

Customer acknowledges that:
- Phishing simulations send emails resembling real phishing attempts to Customer's own employees, for training purposes, using Customer-selected or AI-generated content.
- Customer is solely responsible for ensuring its use of phishing simulation complies with applicable employment law, works council/union requirements, and internal HR policy in its jurisdiction. [PLACEHOLDER: some jurisdictions have specific rules about employee monitoring/testing that must be checked]
- Individual employee phishing results are aggregated by default per the Service's design (see Documentation); Customer may not attempt to circumvent this design to identify individual results without using the Service's documented configuration options, and remains responsible for how it uses any data it does access.

## 7. Acceptable Use

Customer will not: use the Service to send genuinely malicious phishing content outside authorised simulations; attempt to access another tenant's data; reverse-engineer the Service; or use the Service in a manner that violates applicable law.

## 8. AI Features

Certain features (Quiz Generator, Policy Summariser, Phishing Template Generator, Risk Recommendations, Executive Narratives) use third-party AI models. Output may contain inaccuracies and should be reviewed by a qualified human before being relied upon (this is reflected in-product — e.g., AI-generated quiz content is never auto-published to a live assessment without human review). [PLACEHOLDER: standard AI-output disclaimer language, reviewed against applicable AI-specific regulation in target markets]

## 9. Service Level, Support, and Availability

See the separate Service Level Agreement (SLA) document for uptime commitments and remedies.

## 10. Confidentiality

Each party will protect the other's confidential information with reasonable care and use it only for purposes of this agreement. [PLACEHOLDER: standard mutual confidentiality clause, term, and carve-outs]

## 11. Data Protection

See the separate Data Processing Agreement (DPA), which is incorporated by reference and governs the processing of personal data.

## 12. Intellectual Property

Solunar retains all rights in the Service, its underlying software, and platform-provided content (e.g., starter course library, phishing templates). Customer retains rights in Customer Data and any content it authors within the Service.

## 13. Warranties and Disclaimers

[PLACEHOLDER: this section carries significant legal weight — standard SaaS warranty disclaimers, "as is" language, and limitation of implied warranties must be drafted by counsel familiar with the applicable jurisdiction's rules on excluding warranties, which vary significantly between South Africa, Nigeria, and any EU/UK customers who may fall under different consumer-protection floors]

## 14. Limitation of Liability

[PLACEHOLDER: liability cap (commonly tied to fees paid in the preceding 12 months), exclusions for indirect/consequential damages, and carve-outs (data breach, IP infringement, gross negligence) — this is the single highest-stakes clause in this document and must not ship without counsel review]

## 15. Term and Termination

- This agreement continues for the Subscription Term stated in the Order Form and renews per its terms.
- Either party may terminate for the other's uncured material breach.
- Upon termination, Customer may export its data for [PLACEHOLDER: X days], after which Solunar will delete Customer Data per the Data Retention & Deletion Policy.

## 16. Governing Law and Disputes

[PLACEHOLDER: governing law and dispute resolution clause — should reflect where Solunar is incorporated and where its target customers are, and may need jurisdiction-specific variants for different markets rather than one global clause]

## 17. General

[PLACEHOLDER: standard boilerplate — assignment, notices, force majeure, entire agreement, amendment]

---

**Before this document is used with any real customer:**
1. Engage counsel licensed in Solunar's jurisdiction of incorporation and, ideally, familiar with South African and Nigerian commercial/data-protection law given the target markets named in Phase 1.
2. Replace every `[PLACEHOLDER]` with reviewed, jurisdiction-appropriate language.
3. Reconcile Section 5 and Section 8 against the actual data flows in the Architecture and Security Architecture documents — this draft should not claim anything the system doesn't actually do.
4. Have the DPA, SLA, and this document reviewed together, since they cross-reference each other.
