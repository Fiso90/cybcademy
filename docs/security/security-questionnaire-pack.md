# CybCademy — Security Questionnaire Response Pack

Pre-drafted answers to the questions that recur across nearly every enterprise vendor security questionnaire (SIG, CAIQ-style, or custom). Each answer is written to be accurate and specific — pointing at real controls documented elsewhere in this project — rather than generic reassurance. **Before sending any of this to a prospective customer, update the bracketed items and remove/rephrase anything that has changed since this was drafted.**

---

### Do you encrypt data at rest and in transit?
Yes. All traffic is encrypted via TLS 1.2+ end-to-end (edge to origin, origin to database, origin to cache). Data at rest is encrypted via our managed database and object storage providers' native encryption. Sensitive fields (MFA secrets) receive an additional application-layer encryption pass.

### How do you handle multi-tenancy / how do you ensure our data is isolated from other customers?
Tenant isolation is enforced at two independent layers: application-layer scoping (every query is automatically filtered to the requesting tenant) and PostgreSQL Row-Level Security at the database layer, meaning even a hypothetical application-layer bug cannot expose another tenant's data — the database itself refuses to return it. This is verified by automated tests that run on every code change, including a test that deliberately bypasses the application-layer control specifically to confirm the database layer independently holds.

### Do you have multi-factor authentication?
Yes, and it is mandatory (not optional or disableable by a customer administrator) for all privileged roles — system administrators, organisation administrators, compliance officers, auditors, and security officers.

### How do you log and audit access to our data?
All security-relevant actions are recorded in an immutable audit log — timestamped, attributed to the acting user, with before/after state for changes. This log cannot be modified or deleted even by our own application's runtime database credentials; that privilege is explicitly revoked at the database level. Customers with the appropriate role can export a complete audit evidence package for any date range.

### Have you had an independent security assessment / penetration test?
**[Honest current answer, update once this actually happens]:** Not yet completed. We have extensive internal testing covering tenant isolation, access control, and our core business logic, but have not yet engaged an independent third party for a formal assessment. This is planned before broad general availability. *(Note: do not answer this question with anything other than the truth — "yes" when the answer is "not yet" is exactly the kind of claim that damages trust irreparably if discovered, and enterprise security reviewers specifically probe for this.)*

### What is your incident response process?
We maintain a documented Incident Response Plan with defined severity levels, response procedures, and customer notification commitments. [PLACEHOLDER: once the plan's placeholder notification timeframes are finalised with legal input, state the actual commitment here, e.g., "Customers are notified within X hours of a confirmed incident affecting their data."]

### Do you use AI/machine learning features, and how do you handle data sent to AI providers?
Yes — several optional features use a third-party AI provider (Anthropic). All AI requests pass through a single internal gateway component that includes an automated check rejecting any prompt containing detectable email addresses before it's sent externally. Features that summarise organisation-wide data are architecturally restricted to aggregated data only — never individual employee records — regardless of what a user requests.

### Can employees' individual results (e.g., phishing simulation) be seen by their manager?
By default, no — an employee's direct manager sees department-level trends only, not individual results. Only specific security/compliance roles see individual-level data. This is enforced at the API layer, not just hidden in the user interface, so it cannot be bypassed by a technically sophisticated user calling the API directly.

### What is your backup and disaster recovery process?
Automated daily backups, encrypted, stored in a separate failure domain from the primary database, in addition to our managed database provider's own backup/point-in-time-recovery capability. **[Honest current answer, update once completed]:** A full restore drill validating this process end-to-end has not yet been performed; this is scheduled before general availability.

### What is your uptime commitment?
See our Service Level Agreement. **[Honest current answer]:** Our architecture is designed around a 99.5% monthly uptime target, but this has not yet been measured under real production load — we do not want to overstate an unverified number in a security review specifically, since this kind of claim tends to get tested.

### Do you support data residency requirements (data must stay in a specific region/country)?
**[Cannot be answered honestly yet — hosting region has not been finalised as of this draft.]** Once finalised: [state actual hosting region and whether it satisfies the specific residency requirement being asked about — this varies significantly by customer and cannot be pre-answered generically].

### How do you handle employee offboarding / access revocation?
Deactivating an employee in the platform immediately revokes their access, which is logged. [PLACEHOLDER: confirm and state actual session-invalidation timing — e.g., "active sessions are terminated within X" — once verified against the actual implementation rather than assumed].

### Do you conduct background checks / security training for your own staff?
[PLACEHOLDER — this is a Solunar HR/operations question, not something derivable from the technical build; must be answered honestly based on Solunar's actual internal practices, which this project has no visibility into]

---

## How to Use This Pack

- Every answer above that says **"not yet"** or is bracketed should stay that way in any real questionnaire response until it's actually true — this pack is deliberately written to make dishonest completion harder, not easier, by explicitly flagging where the honest answer is currently a gap.
- Update this document whenever a flagged gap closes (e.g., once the independent security assessment happens, update that answer and note the assessment date/scope).
- For any question not covered here, draft the answer by pointing at a specific control in the Security Architecture Document rather than generic reassurance language — that's the pattern every answer above follows, and it's what makes these answers credible to a technical security reviewer.
