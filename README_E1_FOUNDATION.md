# CybCademy — Phase 10 Code Drop: Epic E1 — Platform Foundation

This is the first slice of Phase 10 (Backend Development), covering **Epic E1** from the Phase 9 Development Plan: multi-tenancy, Row-Level Security, authentication, MFA, and RBAC — the foundation every other epic depends on.

## What's included

| Layer | Files |
|---|---|
| **Migrations** | `tenants`, RLS helper (`TenantRls` class), `departments`, `users`, RBAC tables (`roles`, `permissions`, pivots), `audit_logs` |
| **Tenant isolation** | `app/Support/TenantContext.php`, `app/Support/Traits/BelongsToTenant.php`, `app/Http/Middleware/SetTenantContext.php` |
| **Auth & MFA** | `app/Modules/Auth/Services/AuthService.php`, `AuthController.php`, `LoginRequest.php`, `app/Http/Middleware/EnsureMfaVerified.php` |
| **RBAC models** | `app/Modules/Auth/Models/Role.php`, `Permission.php` |
| **Audit logging** | `app/Support/AuditLogger.php` — the single write path into `audit_logs` |
| **Representative vertical slice** | The full Employee module (Controller → Service → Repository → Model → Form Request → Events), demonstrating the layering pattern every other module (Course, Policy, Phishing, Incident, etc.) will follow in subsequent Phase 10 drops |
| **Routes** | `routes/web.php` (session auth), `routes/api.php` (JWT/Sanctum, per Phase 8 endpoint spec) |
| **Kernel** | Middleware registration excerpt |
| **Security test** | `tests/Security/TenantIsolationTest.php` — the automated leak-detection test required by Phase 5, Phase 7, and Phase 9's risk items |

## Design decisions this code enforces

1. **Two-layer tenant isolation, tested independently.** The `BelongsToTenant` trait (application layer) and PostgreSQL RLS (database layer) are both enforced, and `TenantIsolationTest` proves each layer independently — including a test that deliberately bypasses the application scope to confirm RLS alone still holds the line.
2. **MFA is a hard rule, not a suggestion.** `EnsureMfaVerified` checks role membership against the fixed privileged-role list from BR-2, and is not tenant-configurable, matching the SRS wording exactly.
3. **Audit logs have exactly one write path.** Nothing in the Employee module (or any future module) inserts into `audit_logs` directly — everything goes through `AuditLogger`, and the migration revokes UPDATE/DELETE from the application's runtime database role.
4. **Controllers stay thin.** `EmployeeController` and `AuthController` contain no business logic — every decision lives in a Service class, matching Phase 4 Section 4's layering diagram exactly.

## What's deliberately not in this drop

- The remaining Employee endpoints' full test suite (Phase 12)
- Course/Policy/Phishing/Incident modules (Epics E2–E5, subsequent Phase 10 drops per the Phase 9 sprint plan)
- Actual `composer.json`/framework bootstrap files (this environment has no package-registry network access to run `composer create-project`; these files assume they're dropped into an existing Laravel 11 application skeleton)
- Blade view files referenced by `routes/web.php` (`auth.login`, `auth.mfa-challenge`, `dashboard.index`) — UI implementation is Phase 11

## Next steps

Continue Phase 10 with **Epic E2 (Learning Management)** next, per the Phase 9 sprint plan's dependency ordering — or let me know if you'd like a different epic prioritised.
