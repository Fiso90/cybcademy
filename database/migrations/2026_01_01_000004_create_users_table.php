<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

require_once __DIR__ . '/2026_01_01_000002_create_rls_helper_function.php';

/**
 * Creates the `users` table.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase5_Database_Design.md Section 3.2
 * @see /mnt/user-data/outputs/CybCademy_Phase7_Security_Architecture.md Section 3 (MFA)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            $table->string('name', 255);
            $table->string('email', 255);
            $table->string('password_hash', 255);

            // MFA (Phase 7 Section 3) - mandatory for privileged roles,
            // enforced in EnsureMfaVerified middleware, not just at the
            // database layer.
            $table->boolean('mfa_enabled')->default(false);
            $table->text('mfa_secret')->nullable(); // encrypted cast at the model layer

            $table->enum('status', ['active', 'inactive', 'invited'])->default('invited');

            // Denormalised for dashboard read performance (Phase 5 Section 4).
            // Source of truth is the human_risk_scores table.
            $table->decimal('cached_human_risk_score', 5, 2)->nullable();

            $table->timestampTz('last_login_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            // Composite unique per Phase 5 Section 5 - same email may exist
            // across different tenants.
            $table->unique(['tenant_id', 'email']);
            $table->index(['tenant_id', 'department_id']);
            $table->index(['tenant_id', 'status']);
        });

        TenantRls::enable('users');
    }

    public function down(): void
    {
        TenantRls::disable('users');
        Schema::dropIfExists('users');
    }
};
