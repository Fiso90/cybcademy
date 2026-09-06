# CybCademy — API Documentation

## Base URL and Authentication

All endpoints are under `/api/v1/`. Authentication is via JWT bearer token:

```
Authorization: Bearer <token>
```

Obtain a token via `POST /api/v1/auth/token` using a personal access token or client credentials issued by an Organisation Administrator under Settings → API Access.

## Response Envelope

Every response follows the same shape:

```json
{
  "data": {},
  "meta": { "page": 1, "per_page": 25, "total": 143 },
  "errors": []
}
```

Errors use standard HTTP status codes with a consistent error object:

```json
{ "data": null, "meta": [], "errors": [{ "code": "validation_error", "message": "..." }] }
```

`402 Payment Required` specifically indicates a subscription-tier gate (see AI Features below) — not a payment failure in the traditional sense, but "this feature requires a plan upgrade."

## Endpoint Reference by Domain

The full endpoint catalogue is documented per-module in the codebase's own route files, which are the source of truth (this document summarises; the code is authoritative if they ever disagree):

| Domain | Routes file | Key endpoints |
|---|---|---|
| Auth | `routes/web.php` (session) | Login/MFA are session-based, not part of the JWT API |
| Employees | `app/Modules/Employee/routes` (registered in `routes/api.php`) | `GET/POST /employees`, `GET /me/courses` |
| Courses | `app/Modules/LMS/routes.php` | `GET/POST /courses`, `GET /courses/{id}`, `POST /courses/{id}/publish`, `POST /courses/{id}/assign`, lesson CRUD, `POST /assessments/{id}/attempts` |
| Certificates | same | `GET /certificates/verify/{uid}` — the one public, unauthenticated endpoint |
| Policies | `app/Modules/Policy/routes.php` | `GET/POST /policies`, `POST /policies/{id}/publish`, `POST /policies/{id}/acknowledge` |
| Phishing Simulation | `app/Modules/PhishingSimulation/routes.php` + `public_routes.php` | `GET/POST /phishing-campaigns`, `POST /{id}/launch`, `GET /{id}/results` (aggregation behaviour depends on caller's role — see below), plus public click/report tracking links |
| Incidents | `app/Modules/IncidentReporting/routes.php` | `GET/POST /incidents`, `PATCH /incidents/{id}` |
| Analytics | `app/Modules/Analytics/routes.php` | `GET /reports/compliance`, `GET /reports/human-risk-score` |
| Audit Centre | `app/Modules/AuditCentre/routes.php` | `GET /audit-logs`, `POST /audit/export`, `GET /audit/export/{jobId}` |
| AI Features | `app/Modules/AI/routes.php` | `POST /ai/courses/{id}/generate-quiz`, `POST /ai/policies/{id}/summarise`, `POST /ai/phishing-templates/generate`, `GET /ai/executive-narrative`, `GET /ai/risk-recommendations` — each returns `402` if the caller's subscription tier doesn't include it |
| Billing / Notifications / Knowledge Base | `app/Modules/Billing/routes.php` | `GET/POST /notifications`, `GET/POST /knowledge-base` |

## A Note on `/phishing-campaigns/{id}/results`

This endpoint's response shape depends on the caller's role, by design (Business Rule BR-4): Security Officer, Compliance Officer, and Admin roles receive individual-level results (with `user_id` present on each row); every other role receives department-aggregated results (`department_id` + `event_count`, no individual identification possible). This is not a bug or inconsistency — it's the platform's core non-punitive design principle enforced at the API layer, not just the UI. Client applications should check for the presence of a `user_id` field to determine which shape they received, rather than assuming based on their own role, since that's the intended integration pattern (see `resources/views/phishing/index.blade.php` for the reference implementation of this pattern).

## Rate Limiting

Standard endpoints follow the general API throttle. Two endpoints have independent, stricter limits, since both are deliberately public/unauthenticated: certificate verification and phishing simulation tracking links. See the Security Architecture document, Section 10, for the full rationale.

## Full Specification

A generated OpenAPI 3.0 specification (`docs/openapi.yaml`) is referenced throughout the design documents as the authoritative machine-readable contract, but automated generation from the route definitions above has not yet been wired into the build — this remains a documented gap (see Epic E8's scope note in `README_PHASE10_INDEX.md`).
