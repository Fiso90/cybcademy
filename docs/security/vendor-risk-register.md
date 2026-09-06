# CybCademy — Vendor / Third-Party Risk Register

Formal documentation of every third party with access to Customer Data or the production environment, what they can access, and why — the artifact both the DPA's Sub-Processor List and any enterprise customer's security questionnaire will ask for.

**Status:** Reflects the vendors actually referenced across this project's design and DevOps documents. Contract/DPA status with each vendor is marked as [PLACEHOLDER] where not confirmed — this register describes technical integration, not confirmed legal agreements with each vendor, which is separate work.

---

| Vendor | Role | Data Accessed | Why | Sub-Processor DPA in Place? |
|---|---|---|---|---|
| **Anthropic** | AI model provider (Claude) — powers Quiz Generator, Policy Summariser, Phishing Template Generator, Risk Recommendations, Executive Narratives | Prompt content sent by `AiGatewayService`, scoped per-feature (see Section 8 of the DPA draft for the specific data-minimisation controls in place) | Core AI feature functionality (Epic E9) | [PLACEHOLDER — confirm Anthropic's commercial DPA/terms have been reviewed and executed] |
| **Cloudflare** | CDN, DDoS protection, SSL termination, WAF | Encrypted traffic passes through at the network layer; Cloudflare does not have application-level access to decrypted Customer Data beyond what any CDN/edge provider handling HTTPS traffic sees | Edge security and performance (Architecture Document, Section 1) | [PLACEHOLDER] |
| **[Managed PostgreSQL provider — not yet selected]** | Primary data store | All Customer Data | Core data persistence (Architecture Document explicitly recommends a managed/dedicated host rather than self-hosting) | [PLACEHOLDER — cannot be completed until provider is selected, which is itself an open item from Phase 4] |
| **[S3-compatible object storage provider — Cloudflare R2 recommended, not confirmed]** | File storage (certificates, policy documents, database backups) | Uploaded files, encrypted backups | File persistence (Phase 4 Section 10), backup storage (`deploy/backup.sh`) | [PLACEHOLDER] |
| **[Container registry / hosting provider — not yet selected]** | Runs the production Docker containers | Effectively all data, at the infrastructure level (whoever hosts the containers has the deepest access of any vendor in this list) | Compute/hosting | [PLACEHOLDER] — **this is the least-defined vendor relationship in the entire project and deserves priority attention, since it's the one with the broadest access** |
| **GitHub** | Source code repository, CI/CD (GitHub Actions) | Source code; CI pipeline has access to test credentials (not production credentials — confirm this boundary is actually enforced in repository secrets configuration, not just intended) | Version control, CI/CD (Phase 4 Section 11, Phase 13) | [PLACEHOLDER] |

## Access Tiers (For Internal Reference)

- **Tier 1 (deepest access):** hosting/compute provider, database provider — these vendors could technically access raw Customer Data if they chose to or were compromised.
- **Tier 2 (scoped access):** Anthropic (only what's explicitly sent in a prompt, with the PII guardrail as a partial mitigation), object storage (encrypted files, backups).
- **Tier 3 (infrastructure-only):** Cloudflare (encrypted transit), GitHub (code, not data).

## Open Items

1. **Two of the six vendors in this register are not yet selected** (managed database, hosting/compute) — this register cannot be finalised, and neither can the DPA's international-transfer section, until those decisions are made.
2. **No vendor's DPA/contract status has been confirmed as executed** — every row above is marked placeholder for this reason. This register documents the *technical* relationship; a parallel legal review confirming actual signed agreements exist with each vendor is separate, necessary work.
3. **Recommend a recurring review cadence** (e.g., annually, or whenever a new vendor is introduced) — add this to the Maintenance Guide's recurring task list once this register is finalised.
