# CybCademy — Phase 13: DevOps

Implements the Phase 4/9-confirmed deployment architecture (Docker, Nginx + PHP-FPM, isolated queues, GitHub Actions CI/CD) and closes several gaps explicitly flagged and deferred by earlier phases.

## What's included

| Artifact | Purpose |
|---|---|
| `Dockerfile` | Multi-stage build, PHP 8.3-FPM Alpine runtime, production Opcache tuning |
| `docker/nginx/default.conf` | Reverse proxy with Phase 7 Section 9's security headers implemented directly |
| `docker-compose.yml` | Full stack — Nginx, app, three isolated queue workers, scheduler, Redis. PostgreSQL deliberately excluded (see file's own comment) |
| `.env.example` | Every environment variable this codebase actually reads, annotated with which epic introduced it |
| `.github/workflows/ci-cd.yml` | Test + build pipeline, with a **real PostgreSQL service container** |
| `app/Console/Kernel.php` | Scheduling for `RecalculateHumanRiskScores` (Epic E6) and policy reminders (Epic E10) |
| `deploy/deploy.sh`, `rollback.sh`, `backup.sh` | Rolling deployment, rollback, and independent secondary database backup |

## Gaps this phase closes, cross-referenced honestly

1. **The Phase 12 CI risk is directly resolved.** That document warned explicitly that testing against SQLite instead of PostgreSQL would make the RLS-dependent tests "pass for the wrong reason." `ci-cd.yml` runs a real `postgres:16-alpine` service container — this isn't a generic CI setup, it's the specific fix for a specific, previously-named risk.
2. **Two scheduling gaps, open since Phase 10, are closed.** Epic E6's README said cadence wiring was "a one-line addition to `app/Console/Kernel.php` in Phase 13" — it's here now, nightly at 02:00. Epic E10's README said policy reminders were "ready to be invoked on a schedule in Phase 13" — weekly, deliberately not daily, to avoid the reminder becoming the kind of nagging that undermines the non-punitive tone established since Phase 1.
3. **Phase 9 Section 7's migrate-rollback-migrate CI check is implemented for the first time.** That document specified the requirement but had no CI pipeline yet to put it in — this phase is the first to actually build that pipeline, so the check landing here (not earlier) reflects genuine dependency ordering, not an oversight.
4. **Security headers from Phase 7 Section 9 are implemented, not just specified.** The Nginx config's CSP is a real, restrictive policy with explicit allow-list entries for the two third-party origins Phase 11's layout actually uses (cdnjs for Bootstrap/Bootstrap Icons/Alpine.js) — not a wildcard.

## Honestly incomplete

- **No container registry push or remote deployment trigger** — `deploy/README.md` states this plainly rather than implying the pipeline is more automated than it is. Wiring this up requires infrastructure decisions (which registry, which host) no prior phase specified.
- **Backup encryption uses a shared passphrase via GPG**, flagged in the script's own comments as needing to move to a managed KMS key for a production-grade implementation — shown as the concrete minimal version, not represented as finished.
- **Malware scanning (ClamAV) is stubbed in `.env.example` but not integrated** into any upload path — this has been flagged as unselected tooling since Phase 7 and remains so; a real integration point is a genuine remaining task, not something this phase quietly completed.
- **The independent OWASP ASVS Level 2 assessment** (flagged since Phase 3, reiterated in Phase 7 and Phase 12) still has not happened. Nothing in DevOps tooling substitutes for that — it needs to be scheduled as an external engagement.
- **PDF rendering** (certificates, audit exports) remains unselected tooling, as flagged in Epics E2 and E7.

## Next Phase

**Phase 14 — Documentation:** Developer Guide, Administrator Guide, User Manual, Installation Guide, Deployment Guide (much of which can now draw directly on this phase's real Docker/CI/deployment artifacts rather than describing hypothetical infrastructure), API Documentation, Maintenance Guide, Troubleshooting Guide.
