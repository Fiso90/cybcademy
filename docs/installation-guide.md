# CybCademy — Installation Guide

## Prerequisites

- Docker and Docker Compose
- A PostgreSQL 16 instance (local, or a managed provider — see `docker-compose.yml`'s top comment for why this isn't a Compose service in this repo)
- Composer (only needed if building the image locally rather than pulling a pre-built one)
- An Anthropic API key (for AI features — see `.env.example`'s `ANTHROPIC_*` variables)
- An S3-compatible object storage bucket (Cloudflare R2 recommended, per the confirmed architecture) for file storage and database backups

## Step-by-Step Installation

### 1. Clone and configure

```bash
git clone <repository-url> cybcademy
cd cybcademy
cp .env.example .env
```

Fill in `.env` — see `.env.example`'s inline comments for what each variable is and which part of the system reads it. At minimum you need: `APP_KEY` (generate below), `DB_*`, `REDIS_*`, and `AWS_*` (object storage).

### 2. Generate the application key

```bash
docker compose run --rm app php artisan key:generate
```

### 3. Provision the database

Point `DB_HOST` etc. in `.env` at your PostgreSQL 16 instance, then run:

```bash
docker compose run --rm app php artisan migrate
```

This runs every migration from `database/migrations/`, including the Row-Level Security policies each tenant-scoped table requires — **this is not optional infrastructure**, RLS is load-bearing for tenant data isolation across this entire platform. Do not run against a non-PostgreSQL database engine.

Also revokes UPDATE/DELETE privileges on `audit_logs` from the application's runtime database role (see the Epic E1 migration) — confirm your `DB_USERNAME` in `.env` is a least-privileged application role, not a superuser, before running this in production.

### 4. Seed initial data (optional, for a fresh install)

```bash
docker compose run --rm app php artisan db:seed
```

Seeds the base permission catalogue and any platform-wide phishing simulation / knowledge base templates. **Do not run this against a production database that already has real tenant data** — it's intended for a genuinely fresh install.

### 5. Start the stack

```bash
docker compose up -d
```

Starts Nginx, the app container, three isolated queue workers, the scheduler, and Redis. See `docker-compose.yml` for the full service list and why the queues are split the way they are.

### 6. Verify

```bash
curl http://localhost:8080/
```

Should return the login page. Check `docker compose logs app` if not.

## Common Installation Issues

- **"RLS policy already exists" on re-run:** you likely ran `migrate` twice against a database that wasn't fully rolled back first. Use `php artisan migrate:fresh` on a non-production database, never on one with real data.
- **AI features return 500 errors:** confirm `ANTHROPIC_API_KEY` is set and valid — `AiGatewayService` will fail loudly rather than silently degrading, by design.
- **File uploads fail:** confirm `AWS_*` variables point to a real, accessible bucket, and that the bucket is private (never public — see the Security Architecture document's file storage section).

See `docs/troubleshooting-guide.md` for further issues.
