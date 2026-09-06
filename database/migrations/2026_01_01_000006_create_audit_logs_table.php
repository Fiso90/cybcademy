<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/2026_01_01_000002_create_rls_helper_function.php';

/**
 * Creates the `audit_logs` table.
 *
 * This table is append-only by design: no `updated_at`, no `deleted_at`,
 * and - critically - the application's normal database role is granted
 * INSERT and SELECT only. UPDATE/DELETE are revoked at the database level
 * so a compromised application account cannot tamper with historical
 * evidence, only Solunar's documented break-glass DBA procedure can, and
 * that procedure is itself logged outside this table.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase5_Database_Design.md Section 3.10
 * @see /mnt/user-data/outputs/CybCademy_Phase7_Security_Architecture.md Section 7
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('actor_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('action', 150); // e.g. "policy.acknowledged", "user.role_changed"
            $table->string('resource_type', 100);
            $table->uuid('resource_id')->nullable();
            $table->jsonb('before_state')->nullable();
            $table->jsonb('after_state')->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->timestampTz('occurred_at');
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['tenant_id', 'occurred_at']);
            $table->index(['tenant_id', 'resource_type', 'resource_id']);
            $table->index(['tenant_id', 'action']);
        });

        TenantRls::enable('audit_logs');

        // Defence-in-depth per Phase 7 Section 7: revoke UPDATE/DELETE from
        // the application's runtime database role. The role name below is
        // an illustrative placeholder - actual role provisioning is an
        // infrastructure/DevOps (Phase 13) concern, wired to the specific
        // hosting provider's role model.
        DB::statement('REVOKE UPDATE, DELETE ON audit_logs FROM cybcademy_app;');
    }

    public function down(): void
    {
        TenantRls::disable('audit_logs');
        Schema::dropIfExists('audit_logs');
    }
};
