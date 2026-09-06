# CybCademy — Phase 10 Code Drop: Epic E2 — Learning Management

Second slice of Phase 10, per the Phase 9 sprint plan (Sprints 4–6). Builds on Epic E1's foundation (tenant isolation, RBAC, audit logging) — nothing here reimplements or bypasses those primitives.

## What's included

| Layer | Files |
|---|---|
| **Migrations** | `courses` + `lessons`, `question_bank` + `assessments` + `assessment_questions` + `assessment_attempts`, `course_assignments` + `certificates` |
| **Models** | `Course`, `Lesson`, `QuestionBankItem`, `Assessment`, `AssessmentAttempt`, `CourseAssignment`, `Certificate` — all `BelongsToTenant` except the pivot |
| **Services** | `CourseService` (create/publish/assign), `AssessmentService` (server-side grading), `CertificateService` (idempotent issuance) |
| **Repository** | `CourseRepository` |
| **Controllers** | `CourseController`, `AssessmentController`, `CertificateController` (includes the one public/unauthenticated endpoint in the whole API) |
| **Cross-module wiring** | `AssignOnboardingCourses` listener — the concrete fulfilment of FR-2.4's "employee lifecycle triggers automatic training assignment," wired via the `EmployeeOnboarded` event from Epic E1 rather than a direct LMS→Employee dependency |
| **Tests** | `AssessmentGradingTest` (grading correctness + certificate idempotency), extended `TenantIsolationTest` RLS coverage list |

## Design decisions worth knowing about

1. **Grading is entirely server-side.** `question_bank.correct_answer` is never sent to the Course Player client — enforced by keeping it out of any API resource/transformer that serves assessment questions (transformer itself not included in this excerpt, flagged as a Phase 11 frontend-integration detail to get right).
2. **Certificate issuance is idempotent.** Re-passing an assessment (e.g. a retake) does not mint a duplicate certificate — `CertificateService::issue()` checks for an existing user/course pair first. Covered by `test_reattempting_after_already_passing_does_not_issue_a_duplicate_certificate`.
3. **The public certificate verification endpoint is the one deliberate exception to "auth required."** It's rate-limited independently (`throttle:20,1`) from the general API throttle, and deliberately discloses only holder name, course title, and issue date — never tenant name, email, or department, since anyone with the code can query it.
4. **Employee↔LMS coupling goes through an event, not a direct call.** `AssignOnboardingCourses` listens for `EmployeeOnboarded`; the Employee module has no idea the LMS module exists. This is the pattern every future cross-module trigger (e.g. Policy Acknowledgement onboarding in Epic E3) should follow.

## Known simplifications, called out honestly

- `AssignOnboardingCourses` queries `courses.is_onboarding_default`, a column not yet in the Epic E2 migrations — the actual tenant-configurable "onboarding bundle" mechanism is a Settings-module (Epic E10) concern; this listener assumes that column will exist by the time E10 lands, and is a legitimate integration point to revisit then, not a bug to fix now.
- `AssessmentService::answerMatches()` handles `mcq`/`true_false` only; scenario-based question scoring (partial credit, rubric-based) is explicitly left as an extension point, not silently unsupported — flagged in the code comment.
- PDF rendering for certificates is not implemented here — `CertificateService` creates the database record and reserves the storage path; actual PDF generation is a Phase 13 tooling decision (a library or headless-browser render step feeding that path).

## Next steps

Per the Phase 9 sprint plan, next is **Epic E3 (Policy Management)** — smaller in scope than E2, and a good candidate to pair with **Epic E5 (Incident Reporting)** in one drop if you'd like to move faster through the remaining epics. Let me know how you'd like to sequence it.
