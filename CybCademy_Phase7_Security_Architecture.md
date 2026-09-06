# CybCademy — Phase 7: Security Architecture Document

**Product:** CybCademy — Human Cyber Risk Management & Cybersecurity Awareness Platform
**Developed by:** Solunar Informatics
**Document Version:** 1.0
**Date:** 28 July 2026
**Status:** Draft for Approval
**Depends on:** Phase 1–6 (approved)
**Target Standard:** OWASP ASVS Level 2

---

## 1. Threat Model

### 1.1 Assets
- Tenant employee PII (names, emails, department, role)
- Training/assessment records and certificates
- Policy acknowledgement records (legal/compliance evidence)
- Phishing simulation results (sensitive — reveals individual susceptibility)
- Incident reports (may contain sensitive descriptions of real security events)
- Audit logs (evidence of platform-wide activity, tamper-evidence critical)
- Tenant isolation boundary itself (the platform's core trust promise)

### 1.2 Threat Actors
| Actor | Motivation | Primary Concern |
|---|---|---|
| External attacker (unauthenticated) | Data theft, ransomware staging, credential harvesting | Standard web app attack surface (OWASP Top 10) |
| Malicious/compromised tenant user | Access another tenant's data; privilege escalation within own tenant | Tenant isolation failure, RBAC bypass |
| Malicious insider (Solunar staff) | Unauthorised data access across tenants | Least-privilege internal access, break-glass logging |
| Compromised third-party dependency | Supply-chain attack via npm/composer package | Dependency scanning, SBOM, pinned versions |
| Automated bot/credential-stuffing | Account takeover | Rate limiting, MFA, anomaly detection |

### 1.3 STRIDE Summary

| Category | Primary Mitigation |
|---|---|
| Spoofing | MFA (BR-2), strong session management, JWT signature verification |
| Tampering | Immutable audit logs, DB constraints, TLS in transit |
| Repudiation | Audit logs with actor/timestamp/before-after state (Phase 5 Section 3.10) |
| Information Disclosure | Row-Level Security, RBAC, encryption at rest, least-privilege service accounts |
| Denial of Service | Rate limiting, queue-based async processing, Cloudflare edge protection |
| Elevation of Privilege | Three-layer authorization (tenant scope → RBAC → resource policy, Phase 4 Section 7) |

---

## 2. OWASP Top 10 (2021) Mitigations

| Risk | Mitigation |
|---|---|
| A01 Broken Access Control | Tenant-scope + RBAC + resource-policy layering (Phase 4); RLS as DB-level backstop; automated tenant-isolation tests (Phase 5 risk item, executed in Phase 12) |
| A02 Cryptographic Failures | TLS 1.2+ enforced (Cloudflare + origin), Argon2id password hashing, encrypted-at-rest MFA secrets and sensitive JSONB fields, AES-256 for file storage encryption at rest |
| A03 Injection | Eloquent ORM parameterised queries exclusively — no raw SQL string concatenation permitted; input validation via Laravel Form Requests on every endpoint |
| A04 Insecure Design | Threat modelling performed at architecture phase (this document), not retrofitted; non-punitive phishing design (BR-4) is itself a threat-informed design decision protecting against the "security fatigue" failure mode |
| A05 Security Misconfiguration | Hardened Docker base images, no debug mode in production, secrets via environment injection (never committed), automated configuration baseline check in CI/CD |
| A06 Vulnerable/Outdated Components | Automated dependency scanning (Dependabot/Composer audit) in GitHub Actions, scheduled patch review cadence |
| A07 Identification & Authentication Failures | MFA mandatory for privileged roles, account lockout with rate limiting, secure password reset flow (time-limited, single-use tokens), no password hints/security questions |
| A08 Software & Data Integrity Failures | Signed container images, CI/CD pipeline integrity (branch protection, required reviews), Subresource Integrity for any CDN-loaded assets |
| A09 Security Logging & Monitoring Failures | Audit log table (immutable) + application log aggregation + queue/uptime monitoring (Phase 4 Section 13), alerting on anomalous patterns (e.g. mass data export, repeated auth failures) |
| A10 Server-Side Request Forgery | Outbound requests (e.g. AI API calls, webhook integrations) restricted via allow-listed destinations; no user-controlled URL fetching without validation |

---

## 3. Authentication & MFA

- Password hashing: Argon2id (preferred) with bcrypt as Laravel-default fallback if Argon2id unavailable in a given environment.
- MFA: TOTP-based (RFC 6238), mandatory for System Administrator, Organisation Administrator, Compliance Officer, Internal/IS Auditor, Security Officer roles (BR-2); optional but encouraged (with in-app nudges) for Trainer, Manager, Employee.
- SSO (OAuth2) available for Enterprise-tier tenants; SSO login still subject to the same MFA policy at the identity provider level (CybCademy does not weaken MFA enforcement just because SSO is used).
- Session cookies: `HttpOnly`, `Secure`, `SameSite=Lax` (or `Strict` where UX permits), short-to-moderate expiry with sliding renewal, invalidated server-side on logout (not just cookie deletion).

## 4. Password Policies

- Configurable per tenant (Phase 2 FR-1.5): minimum 12 characters default, complexity rules, expiry optional (NIST guidance favours length/breach-checking over forced rotation, offered as tenant configuration rather than platform mandate, since tenant compliance frameworks may have differing requirements).
- Passwords checked against known-breach lists (e.g. Have I Been Pwned k-anonymity API) at set time, rejecting compromised passwords regardless of complexity score.

## 5. Encryption

- **In transit:** TLS 1.2+ enforced end-to-end (Cloudflare edge to origin, origin to database, origin to Redis).
- **At rest:** Database-level encryption (managed PostgreSQL provider's encryption-at-rest, or LUKS/filesystem encryption if self-hosted); application-level encryption for especially sensitive fields (MFA secrets, API token hashes) using Laravel's encrypted casts.
- **File storage:** Server-side encryption at rest on S3-compatible storage (R2/S3 native SSE), private buckets only, signed time-limited URLs for access.

## 6. Secure Session Management

- Sessions stored in Redis, not in database (performance + natural TTL expiry).
- Session fixation prevented — session ID regenerated on privilege change (login, role change, MFA completion).
- Concurrent session visibility/management available to users (Enterprise tier: admin-forced session revocation for offboarded employees, addressing the lifecycle requirement from Phase 2 FR-2.4).

## 7. Audit Logging

- Detailed in Phase 5 Section 3.10 (schema) and Phase 4 Section 12 (architecture). Security-specific additions:
  - Audit log write path uses a dedicated, minimally-privileged database role (INSERT-only, no UPDATE/DELETE grant at the database level — enforced by PostgreSQL privileges, not just application logic).
  - Any direct database access outside the application (e.g. for support/debugging) is itself logged via a documented break-glass procedure — no untracked superuser access to tenant data in normal operations.

## 8. Backups

- Automated daily full backups, PostgreSQL WAL-based point-in-time recovery for finer RPO where the hosting provider supports it.
- Backup encryption at rest, backups stored in a separate failure domain from the primary database.
- Quarterly restore drills (not just backup verification) — a backup that has never been test-restored is not a validated backup; scheduled as an operational task in Phase 13.
- RPO ≤ 24h / RTO ≤ 4h per Phase 2 NFR, revisited if WAL-based PITR allows tighter RPO at acceptable cost.

## 9. Security Headers

Applied at Nginx/application layer: `Strict-Transport-Security`, `Content-Security-Policy` (restrictive default-src, explicit allow-list for any third-party script origins), `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY` (or CSP `frame-ancestors`), `Referrer-Policy: strict-origin-when-cross-origin`.

## 10. Rate Limiting

- Login endpoint: aggressive per-IP and per-account rate limiting with exponential backoff, distinct from general API rate limiting.
- API tokens: per-token rate limits (Phase 4 Section 5), tiered by subscription level.
- Phishing simulation send endpoints: rate-limited to prevent a compromised admin account being used to spam mass phishing sends beyond configured campaign parameters.

## 11. CSRF

- Laravel's built-in CSRF token validation on all state-changing web (session-authenticated) routes. API routes (JWT-authenticated, stateless) are not CSRF-vulnerable by design but are protected against cross-origin abuse via CORS allow-listing.

## 12. XSS

- Blade's automatic output escaping (`{{ }}`) used by default everywhere; `{!! !!}` raw output usage is prohibited except in a documented, reviewed allow-list of cases (e.g. rendering sanitised rich-text policy content), each requiring explicit sanitisation (e.g. HTMLPurifier) before raw render.
- CSP (Section 9) as defence-in-depth against any XSS that slips through.

## 13. SQL Injection

- Eloquent ORM parameterised queries exclusively (A03 mitigation, restated here as a hard rule); any raw query (rare, performance-critical cases only) requires parameter binding and a code-review sign-off flag.

## 14. File Upload Security

- Applies to: policy document uploads, course content uploads, employee CSV import, certificate templates.
- File type allow-listing (not deny-listing) by MIME type and extension, validated server-side (not trusting client-supplied MIME type).
- Files scanned for malware before being made available for download (integration point flagged for Phase 13 — antivirus/scanning service selection).
- Uploaded files stored outside the web root, served only via signed URLs through the application, never directly executable.
- File size limits enforced per upload type to prevent storage abuse/DoS.

---

## Deliverables

- This Security Architecture Document (threat model, OWASP Top 10 mitigations, authentication/MFA/password policy, encryption, session management, audit logging, backups, security headers, rate limiting, CSRF/XSS/SQLi/file-upload defences)

## Assumptions

- A managed PostgreSQL/hosting provider will offer encryption-at-rest and WAL-based backup capability natively; self-hosting would require these to be configured manually and verified independently.
- Malware scanning for file uploads will use a third-party service or open-source scanner (e.g. ClamAV) integrated at upload time — specific tool selection deferred to Phase 13.
- Independent OWASP ASVS Level 2 assessment (Phase 3 Section 17 risk item) will be scheduled with sufficient lead time before GA to allow remediation of findings.

## Risks

- MFA enforcement for privileged roles is a hard business rule (BR-2), but SSO-based Enterprise tenants introduce a dependency on the tenant's own identity provider correctly enforcing MFA — this should be documented as a shared-responsibility item in customer-facing security documentation, not assumed to be fully within CybCademy's control.
- File upload malware scanning is flagged but not yet tool-selected — if deferred too long, course content and policy uploads could ship in Phase 10/11 without this control in place; recommend prioritising tool selection early in Phase 13 rather than treating it as a late add-on.
- Backup restore drills are easy to skip once "the backups are running fine" — recommend this be a recurring calendar item post-launch, not a one-time pre-launch checklist item.

## Next Phase

**Phase 8 — API Design:** REST endpoint specification across Authentication, Organisation, Employee, Course, Assessment, Certificate, Reporting, and Incident domains, culminating in a full OpenAPI Specification.
