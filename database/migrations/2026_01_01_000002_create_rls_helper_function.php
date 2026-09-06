<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registers the reusable Row-Level Security policy statement applied to
 * every tenant-scoped table.
 *
 * Per Phase 4 Section 8 / Phase 5 Section 6, RLS is the database-level
 * backstop beneath application-layer tenant scoping. The intent, per Phase 5
 * Section 6, is "opt-out, not opt-in": every migration that creates a
 * tenant-scoped table MUST call TenantRls::enable($table) in the same
 * migration, immediately after the table is created. There is no global
 * default RLS applies automatically to every new table in PostgreSQL, so
 * this is enforced by code-review convention plus the automated test in
 * tests/Security/TenantIsolationRlsCoverageTest.php (Phase 12), which fails
 * CI if any tenant_id-bearing table lacks a matching pg_policies row.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase5_Database_Design.md Section 6
 */
return new class extends Migration
{
    public function up(): void
    {
        // Session variable app.current_tenant is set per-request by
        // App\Http\Middleware\SetTenantContext before any tenant-scoped
        // query executes. Defaulting it to '' (rather than leaving it
        // unset) means a request that somehow skips the middleware sees
        // zero rows rather than an error that could be caught and ignored -
        // fail closed, not open.
        DB::statement("ALTER DATABASE " . DB::getDatabaseName() . " SET app.current_tenant = '';");
    }

    public function down(): void
    {
        // Intentionally left blank - reverting a database-level default
        // setting is not meaningful in isolation from the tables that rely
        // on it.
    }
};

/**
 * Static helper invoked from within table-creation migrations.
 *
 * Usage inside a migration's up() method, immediately after Schema::create():
 *
 *   TenantRls::enable('employees');
 */
class TenantRls
{
    public static function enable(string $table): void
    {
        DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY;");
        DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY;");
        DB::statement(<<<SQL
            CREATE POLICY tenant_isolation_{$table} ON {$table}
                USING (tenant_id = NULLIF(current_setting('app.current_tenant', true), '')::uuid)
                WITH CHECK (tenant_id = NULLIF(current_setting('app.current_tenant', true), '')::uuid);
        SQL);
    }

    public static function disable(string $table): void
    {
        DB::statement("DROP POLICY IF EXISTS tenant_isolation_{$table} ON {$table};");
        DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY;");
    }
}
