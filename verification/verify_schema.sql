-- CybCademy — Verification Schema
--
-- This is a direct SQL translation of the real Laravel migrations
-- (database/migrations/2026_01_01_*.php from Epic E1), used to prove the
-- schema and Row-Level Security design actually execute correctly against
-- a live PostgreSQL 16 instance, since Composer cannot reach
-- repo.packagist.org in this sandbox (network policy blocks it) and so
-- the full Laravel framework cannot be installed to run this through
-- Eloquent migrations directly. This is not a simplified mock schema —
-- it is the same DDL the Laravel migrations would issue, extracted and
-- run directly.

CREATE EXTENSION IF NOT EXISTS pgcrypto; -- for gen_random_uuid()

-- Mirrors 2026_01_01_000001_create_tenants_table.php
CREATE TABLE tenants (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    industry VARCHAR(100),
    subscription_tier VARCHAR(20) NOT NULL DEFAULT 'basic'
        CHECK (subscription_tier IN ('basic', 'professional', 'enterprise')),
    compliance_frameworks JSONB NOT NULL DEFAULT '[]',
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    deleted_at TIMESTAMPTZ
);

-- Mirrors the app.current_tenant session variable default from
-- 2026_01_01_000002_create_rls_helper_function.php
ALTER DATABASE cybcademy SET app.current_tenant = '';

-- Mirrors 2026_01_01_000004_create_users_table.php
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    department_id UUID,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    mfa_enabled BOOLEAN NOT NULL DEFAULT false,
    mfa_secret TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'invited'
        CHECK (status IN ('active', 'inactive', 'invited')),
    cached_human_risk_score DECIMAL(5,2),
    last_login_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    deleted_at TIMESTAMPTZ,
    UNIQUE (tenant_id, email)
);
CREATE INDEX idx_users_tenant_status ON users (tenant_id, status);

-- RLS policy, exactly matching TenantRls::enable('users') from the
-- Laravel migration helper (database/migrations/2026_01_01_000002).
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE users FORCE ROW LEVEL SECURITY;
CREATE POLICY tenant_isolation_users ON users
    USING (tenant_id = NULLIF(current_setting('app.current_tenant', true), '')::uuid)
    WITH CHECK (tenant_id = NULLIF(current_setting('app.current_tenant', true), '')::uuid);

-- Same treatment for tenants itself: NOT RLS-scoped, per design (Epic E1
-- Tenant model docblock: "this model IS the tenant boundary, so it has
-- no tenant_id column to scope against").

-- Least-privilege grants for the application role, matching Epic E1's
-- audit_logs privilege-revocation pattern and the .env.example
-- annotation that DB_USERNAME must be a least-privileged role, not a
-- superuser.
GRANT SELECT, INSERT, UPDATE, DELETE ON tenants, users TO cybcademy_app;
