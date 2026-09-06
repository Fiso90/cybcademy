<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for "which tenant is this request operating as."
 *
 * Set once per request by App\Http\Middleware\SetTenantContext, read by
 * App\Support\Traits\BelongsToTenant on every tenant-scoped model query.
 * Also mirrors the value into the PostgreSQL session variable
 * `app.current_tenant` so that Row-Level Security policies (Phase 5
 * Section 6) see the same tenant boundary the application layer does -
 * these two must never disagree.
 *
 * BUG FOUND AND FIXED during live end-to-end verification against a real
 * PostgreSQL 16 instance (no mocks): the original implementation used
 * set_config(..., is_local => true), which scopes the setting to the
 * CURRENT TRANSACTION ONLY. Under PHP-FPM (the confirmed deployment
 * model, Phase 4 Section 11) - one process per request, no implicit
 * wrapping transaction around the whole request - every individual query
 * PDO sends without an explicit BEGIN runs in its own auto-committed
 * transaction. That means the local setting was reset the instant the
 * SET statement's own implicit transaction ended, so EVERY subsequent
 * query in the same request saw app.current_tenant as empty again. Since
 * the RLS policies fail closed on an empty/unset tenant (by design, per
 * the migration helper's docblock), the practical effect in production
 * would have been: every tenant-scoped query, for every user, on every
 * request after the very first internal SET statement, returns zero
 * rows. Not a security leak - the opposite failure mode, a completely
 * non-functional application - but a severe bug either way, and one that
 * unit/feature tests using Laravel's typical RefreshDatabase-in-one-
 * transaction test pattern would not have caught, since that pattern
 * incidentally keeps everything in one real transaction where
 * is_local=true does persist across statements.
 *
 * Fix: use is_local => false (session-scoped, persists for the life of
 * the connection). This is safe specifically because PHP-FPM tears down
 * its PDO connection at the end of every request (no connection
 * pooling/reuse across unrelated requests) - the original comment's
 * concern about "leaking across pooled connections reused by later,
 * unrelated requests" is a real risk under a persistent-worker model
 * (e.g. Laravel Octane/Swoole), but does not apply to the PHP-FPM model
 * this project actually specifies. If this codebase is ever migrated to
 * Octane or a connection-pooled setup (pgbouncer in transaction pooling
 * mode), this decision must be revisited - session-level state would
 * then genuinely risk leaking between requests sharing a connection, and
 * wrapping each request in an explicit transaction would be the correct
 * fix at that point instead of reverting to is_local=true.
 */
final class TenantContext
{
    private static ?string $tenantId = null;

    public static function set(string $tenantId): void
    {
        self::$tenantId = $tenantId;

        DB::statement('SELECT set_config(\'app.current_tenant\', ?, false)', [$tenantId]);
    }

    public static function current(): ?string
    {
        return self::$tenantId;
    }

    public static function clear(): void
    {
        self::$tenantId = null;
        DB::statement('SELECT set_config(\'app.current_tenant\', \'\', false)');
    }
}
