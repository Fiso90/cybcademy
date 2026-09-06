<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

require_once __DIR__ . '/2026_01_01_000002_create_rls_helper_function.php';

/**
 * Creates `policies` and `policy_acknowledgements`.
 *
 * `policy_acknowledgements` is append-only per BR-3 (Phase 2 SRS Section 6:
 * "Policy acknowledgement records are immutable once submitted;
 * corrections require a new versioned acknowledgement, not an edit") - no
 * `updated_at`, and the application layer (PolicyAcknowledgementService)
 * never issues an UPDATE against this table, only INSERT.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase5_Database_Design.md Section 3.6
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policies', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('title', 255);
            $table->unsignedInteger('version');
            $table->string('file_path', 500);
            $table->timestampTz('published_at')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();

            // A tenant may have multiple versions of "the same" policy
            // (same title, incrementing version) but not two rows at the
            // same version for the same title - prevents accidental
            // duplicate publication.
            $table->unique(['tenant_id', 'title', 'version']);
            $table->index(['tenant_id', 'published_at']);
        });
        TenantRls::enable('policies');

        Schema::create('policy_acknowledgements', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('policy_id')->constrained('policies')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestampTz('acknowledged_at');
            $table->string('ip_address', 45)->nullable();
            $table->timestampTz('created_at')->useCurrent();

            // A user acknowledges a specific policy VERSION once - a new
            // policy version requires a new acknowledgement row, which is
            // exactly the "new row, not an edit" correction mechanism BR-3
            // specifies.
            $table->unique(['tenant_id', 'policy_id', 'user_id']);
            $table->index(['tenant_id', 'user_id']);
        });
        TenantRls::enable('policy_acknowledgements');
    }

    public function down(): void
    {
        TenantRls::disable('policy_acknowledgements');
        TenantRls::disable('policies');
        Schema::dropIfExists('policy_acknowledgements');
        Schema::dropIfExists('policies');
    }
};
