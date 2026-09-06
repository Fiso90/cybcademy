# CybCademy — Deployment Guide

This guide assumes the infrastructure decisions made throughout this project's design phases: Ubuntu Linux host, Docker, Nginx + PHP-FPM, Cloudflare for SSL/CDN/WAF, a managed PostgreSQL 16 instance separate from the application containers.

## Production Deployment Flow

1. **CI runs on every push** (`.github/workflows/ci-cd.yml`) — full PHPUnit suite against a real PostgreSQL service container, PSR-12 style check, and a migration reversibility check. Merges to `main` additionally build the application image.
2. **Manual step (not yet automated — see `deploy/README.md`):** an operator pulls the built image to the target host.
3. **Deploy:**
   ```bash
   ./deploy/deploy.sh <image-tag>
   ```
   Runs migrations, then performs a rolling restart of the app and all worker containers one at a time (so Nginx never has zero healthy backends), then health-checks the result.
4. **If something goes wrong:**
   ```bash
   ./deploy/rollback.sh
   ```
   Reverts to the previously-deployed tag. Deliberately does **not** roll back the database — see the script's own comments on the expand/contract migration discipline this depends on. A migration that isn't backward-compatible with the previous release should never ship in the same deploy as removing the code that relied on the old schema shape.

## Zero-Downtime Requirement

The platform's 99.5% v1.0 uptime target (Non-Functional Requirements) implies deployments cannot cause visible downtime. `deploy.sh`'s one-service-at-a-time restart is the mechanism for this — confirm your target host has enough resources to run both the old and new container briefly overlapping during each service's restart.

## Database Migrations in Production

Migrations run **before** the application cutover, against a database still serving the previous code version for the duration of the rolling restart. This means:

- New columns must be nullable or have a default (the old code doesn't know to populate them)
- Dropping a column the old code still reads must wait for a subsequent release, after the code that used it is gone (the "contract" step of expand/contract)
- Renaming a column in one migration is two releases: add the new column and dual-write, then a later release drops the old one

## SSL and DNS

SSL termination happens at Cloudflare, not on the origin host — `docker-compose.yml`'s Nginx service listens on plain HTTP internally. Confirm Cloudflare's SSL mode is set to "Full (strict)" if the origin also has a certificate, or "Flexible" only if you accept the trade-offs that mode implies for the Cloudflare-to-origin leg.

## Environment-Specific Configuration

Production `.env` should never be committed. Recommended: a secrets manager (AWS Secrets Manager, Cloudflare's own secrets store, or equivalent) injecting environment variables at container start, rather than a plaintext `.env` file sitting on the host.

## Backups

`deploy/backup.sh` runs a secondary, independent database backup (encrypted, uploaded to object storage) — this complements, not replaces, your managed PostgreSQL provider's native backup/PITR feature. Schedule it via the host's cron or your orchestration platform's scheduled task feature, daily.

**Restore drills are not automated and should not be.** See `deploy/README.md`'s explicit reminder — a backup that's never been test-restored isn't validated. Put a recurring calendar reminder on this, owned by a named person.

## Monitoring

Not yet fully specified — see the Architecture Document's Monitoring section, which flags tool selection (self-hosted vs. managed APM) as deferred to whoever operationalises this deployment. At minimum, monitor: queue depth on all three queues (a backlog on `audit-exports` specifically threatens the FR-8.2 5-minute SLA), container health, and the p95 latency targets from the Non-Functional Requirements.
