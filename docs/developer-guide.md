# CybCademy — Developer Guide

## 1. Getting Started

```bash
git clone <repository-url> cybcademy
cd cybcademy
cp .env.example .env
composer install
php artisan key:generate
docker compose up -d redis nginx app
docker compose run --rm app php artisan migrate
```

See `.env.example` for every variable the codebase actually reads, each annotated with the epic that introduced it. You will need a real PostgreSQL 16 instance (local or managed) — `DB_HOST`/`DB_PORT`/`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` in `.env` point to it. PostgreSQL is deliberately not a Docker Compose service in this repo — see `docker-compose.yml`'s top comment for why.

## 2. Architecture Orientation

Read these two documents first, in order, before touching code:

1. `README_PHASE10_INDEX.md` — the backend map. Explains the module structure, the two-layer tenant isolation model, and the "business rule enforced in exactly one place" pattern that recurs across every module.
2. `README_PHASE11_FRONTEND.md` — the frontend map, including three real gap-fixes (one a genuine security fix) that were caught by building screens against the real API rather than mocked data.

## 3. Module Structure

Every domain lives under `app/Modules/{ModuleName}/`, following:

```
Controllers/   - thin, validate + delegate only
Services/      - business logic lives here
Repositories/  - query logic, tenant-scoping enforcement point
Models/        - Eloquent models, use App\Support\Traits\BelongsToTenant
Requests/      - Form Request validation + authorization
Events/        - cross-module communication points
Listeners/     - the other side of that communication
```

**Adding a new tenant-scoped table?** Every migration that creates one must call `TenantRls::enable('table_name')` in the same migration (see `database/migrations/2026_01_01_000002_create_rls_helper_function.php`) and the model must `use BelongsToTenant`. Add the table to `tests/Security/TenantIsolationTest.php`'s `tenantScopedTables()` list — this is hand-maintained deliberately, so a missing entry is visible in code review rather than silently passing.

**Adding a business rule that needs enforcing?** Look at how BR-2 (`EnsureMfaVerified` middleware), BR-3 (`PolicyAcknowledgementService`), and BR-4 (`PhishingResultsRepository`) are each implemented in exactly one place, with every consumer calling through that one place rather than reimplementing the check. This is the single most repeated architectural pattern in the codebase — follow it.

## 4. Running Tests

```bash
php artisan test
```

Requires a real PostgreSQL connection (see `.env`) — RLS-dependent tests (`tests/Security/`) will pass against SQLite for the wrong reason (the feature isn't even present), so don't switch the test DB connection without understanding that trade-off. See `README_PHASE12_TEST_PLAN.md` for full coverage inventory and known gaps (no performance or accessibility test automation yet).

## 5. Coding Standards

- PSR-12, enforced in CI (`.github/workflows/ci-cd.yml`) via PHP_CodeSniffer
- Every public method that isn't self-explanatory gets a PHPDoc block explaining the *why*, not just the *what* — the codebase leans heavily on comments that explain design decisions and trade-offs, not just parameter types. Match that style.
- No raw SQL string concatenation — Eloquent parameterised queries only (Phase 7 Section 2, A03)

## 6. AI Feature Development

All AI calls go through `App\Modules\AI\Services\AiGatewayService` — never call the Anthropic API directly from a feature service. See that class's docblock for the three reasons (PII guardrail, usage audit logging, tier-gating) this matters.

## 7. Common Pitfalls (learned the hard way during this project)

- **Forgetting `TenantContext::set()` in a queued job.** Queue workers run outside the HTTP request cycle, so `SetTenantContext` middleware never runs — every job that touches tenant-scoped data must call `TenantContext::set($tenantId)` explicitly as its first step. See `SendPhishingSimulationEmail` and `GenerateAuditExportPackage` for the pattern.
- **Eager-loading a relation that includes sensitive fields.** `CourseController::show()` originally leaked `question_bank.correct_answer` this way — see `README_PHASE11_FRONTEND.md`'s third slice for the full story and `CourseShowHidesCorrectAnswersTest` for the regression test. When adding a new `with()` eager load, ask what fields it's actually exposing to the response.
- **Assuming `dotfiles` get copied by `cp -r dir/*`.** They don't (shell glob behaviour, not a Laravel issue) — bit us during Phase 13 packaging. Use `cp -r dir/. dest/` or explicit dotfile copies when scripting deployments.
