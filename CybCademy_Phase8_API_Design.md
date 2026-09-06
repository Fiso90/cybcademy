# CybCademy — Phase 8: API Design Document

**Product:** CybCademy — Human Cyber Risk Management & Cybersecurity Awareness Platform
**Developed by:** Solunar Informatics
**Document Version:** 1.0
**Date:** 28 July 2026
**Status:** Draft for Approval
**Depends on:** Phase 1–7 (approved)

---

## 1. API Conventions

- Base path: `/api/v1/`
- Authentication: JWT bearer token (`Authorization: Bearer <token>`), issued per Phase 4 Section 6/Phase 7 Section 3.
- Content type: `application/json` for all request/response bodies.
- Response envelope:
```json
{
  "data": { },
  "meta": { "page": 1, "per_page": 25, "total": 143 },
  "errors": []
}
```
- Error responses use standard HTTP status codes (400, 401, 403, 404, 422, 429, 500) with a consistent error object: `{ "code": "validation_error", "message": "...", "details": {} }`.
- Pagination: cursor or offset-based (`?page=`, `?per_page=`, max 100 per page) on all list endpoints.
- Rate limits: per Phase 7 Section 10, returned via `X-RateLimit-Limit` / `X-RateLimit-Remaining` headers.
- All endpoints are tenant-scoped implicitly via the authenticated token's tenant context — no endpoint accepts a client-supplied `tenant_id` parameter that could override this (defence against IDOR/tenant-boundary bypass).

---

## 2. Authentication Endpoints

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/v1/auth/token` | Exchange client credentials / personal access token for a short-lived JWT |
| POST | `/api/v1/auth/token/refresh` | Refresh an expiring JWT |
| POST | `/api/v1/auth/token/revoke` | Revoke a token (e.g. on integration decommission) |

## 3. Organisation Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/organisation` | Retrieve current tenant's organisation profile |
| PATCH | `/api/v1/organisation` | Update organisation profile (Admin only) |
| GET | `/api/v1/organisation/departments` | List departments |
| POST | `/api/v1/organisation/departments` | Create department |
| PATCH | `/api/v1/organisation/departments/{id}` | Update department |
| DELETE | `/api/v1/organisation/departments/{id}` | Soft-delete department |

## 4. Employee Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/employees` | List employees (filterable by department, status, role) |
| POST | `/api/v1/employees` | Create employee |
| GET | `/api/v1/employees/{id}` | Retrieve employee detail |
| PATCH | `/api/v1/employees/{id}` | Update employee |
| DELETE | `/api/v1/employees/{id}` | Deactivate/soft-delete employee (triggers lifecycle offboarding per FR-2.4) |
| POST | `/api/v1/employees/import` | Bulk CSV import (async — returns job ID, per FR-2.3) |
| GET | `/api/v1/employees/import/{job_id}` | Check import job status/results |

## 5. Course Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/courses` | List courses |
| POST | `/api/v1/courses` | Create course (draft) |
| GET | `/api/v1/courses/{id}` | Retrieve course detail (lessons, structure) |
| PATCH | `/api/v1/courses/{id}` | Update course |
| POST | `/api/v1/courses/{id}/publish` | Publish course |
| DELETE | `/api/v1/courses/{id}` | Archive course |
| POST | `/api/v1/courses/{id}/assign` | Assign course to users/departments/roles |
| GET | `/api/v1/courses/{id}/assignments` | List assignment status for a course |
| GET | `/api/v1/employees/{id}/courses` | List an employee's assigned courses and progress |

## 6. Assessment Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/assessments` | List assessments |
| POST | `/api/v1/assessments` | Create assessment |
| GET | `/api/v1/assessments/{id}` | Retrieve assessment detail |
| POST | `/api/v1/assessments/{id}/attempts` | Submit an assessment attempt |
| GET | `/api/v1/assessments/{id}/attempts` | List attempts (Admin/Trainer view) |
| GET | `/api/v1/question-bank` | List question bank items |
| POST | `/api/v1/question-bank` | Create question |

## 7. Certificate Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/certificates` | List certificates issued (filterable by employee, course) |
| GET | `/api/v1/certificates/{id}` | Retrieve certificate detail/download link |
| GET | `/api/v1/certificates/verify/{certificate_uid}` | Public endpoint — verify certificate authenticity (no auth required, rate-limited) |

## 8. Reporting Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/reports/compliance` | Compliance dashboard data export |
| GET | `/api/v1/reports/human-risk-score` | Human Risk Score trend data (org/department/employee scoped by RBAC) |
| POST | `/api/v1/audit/export` | Generate audit evidence export package (async — returns job ID, per FR-8.2) |
| GET | `/api/v1/audit/export/{job_id}` | Check export job status / retrieve download link |
| GET | `/api/v1/audit-logs` | Query audit logs (Auditor/Compliance role only, paginated, filterable) |

## 9. Incident Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/incidents` | List incidents (filterable by status, category) |
| POST | `/api/v1/incidents` | Submit incident report |
| GET | `/api/v1/incidents/{id}` | Retrieve incident detail |
| PATCH | `/api/v1/incidents/{id}` | Update status/assignment (Security Officer only) |

## 10. Phishing Simulation Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/phishing-campaigns` | List campaigns |
| POST | `/api/v1/phishing-campaigns` | Create campaign |
| POST | `/api/v1/phishing-campaigns/{id}/launch` | Launch scheduled campaign |
| GET | `/api/v1/phishing-campaigns/{id}/results` | Retrieve results (aggregated per BR-4 unless caller has Security Officer/Admin/Compliance role) |

## 11. Policy Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/policies` | List policies |
| POST | `/api/v1/policies` | Upload new policy/version |
| GET | `/api/v1/policies/{id}/acknowledgements` | List acknowledgement status |
| POST | `/api/v1/policies/{id}/acknowledge` | Record employee acknowledgement |

---

## 12. OpenAPI Specification (Excerpt)

Full specification to be maintained as `docs/openapi.yaml` and generated/validated in CI (Phase 13). Representative excerpt:

```yaml
openapi: 3.0.3
info:
  title: CybCademy API
  version: "1.0"
  description: REST API for the CybCademy Human Cyber Risk Management Platform
servers:
  - url: https://api.cybcademy.com/api/v1
security:
  - bearerAuth: []
components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
  schemas:
    Employee:
      type: object
      properties:
        id: { type: string, format: uuid }
        email: { type: string, format: email }
        department_id: { type: string, format: uuid, nullable: true }
        status: { type: string, enum: [active, inactive, invited] }
        cached_human_risk_score: { type: number, nullable: true }
    Error:
      type: object
      properties:
        code: { type: string }
        message: { type: string }
        details: { type: object }
paths:
  /employees:
    get:
      summary: List employees
      parameters:
        - in: query
          name: department_id
          schema: { type: string, format: uuid }
        - in: query
          name: status
          schema: { type: string, enum: [active, inactive, invited] }
      responses:
        "200":
          description: Paginated list of employees
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      $ref: "#/components/schemas/Employee"
    post:
      summary: Create employee
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: "#/components/schemas/Employee"
      responses:
        "201":
          description: Employee created
        "422":
          description: Validation error
          content:
            application/json:
              schema:
                $ref: "#/components/schemas/Error"
```

---

## Deliverables

- This API Design Document with endpoint catalogue across all domains
- OpenAPI Specification (excerpt above; full spec maintained as a living `docs/openapi.yaml` artifact alongside the codebase)

## Assumptions

- Async job pattern (job ID + polling endpoint) is acceptable for long-running operations (CSV import, audit export) rather than webhooks in v1.0; webhook callbacks are a reasonable post-v1.0 enhancement for Enterprise-tier integrations.
- API consumers are primarily internal (the CybCademy frontend itself, via session auth) and Enterprise-tier integration partners (via JWT) — no public developer ecosystem/marketplace is assumed for v1.0.

## Risks

- The public, unauthenticated certificate verification endpoint (`/certificates/verify/{certificate_uid}`) is a deliberate exception to "everything requires auth" and must be rate-limited and monitored specifically, since it's the one intentionally exposed surface — flagged for explicit inclusion in Phase 12 security testing rather than being assumed "low risk because it's read-only."
- Phishing campaign results endpoint enforcing BR-4's aggregation-by-default rule at the API layer (not just the UI layer) is essential — a UI-only restriction would leave the data exposed to anyone calling the API directly; this document specifies API-layer enforcement explicitly to close that gap.

## Next Phase

**Phase 9 — Development Plan:** Breaking the project into epics, features, and tasks; sprint plan; Git and branching strategy; release strategy — producing the Development Roadmap that will govern Phases 10–15.
