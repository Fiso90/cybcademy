<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

require_once __DIR__ . '/2026_01_01_000002_create_rls_helper_function.php';

/**
 * Creates `audit_exports` - tracks the status of an async audit evidence
 * export job (Phase 8 Section 8's "POST returns job ID, GET polls status"
 * pattern), fulfilling FR-8.2's 5-minute SLA and the requirement that
 * an export carries "tamper-evident metadata (generation timestamp,
 * generating user, record count)" per Phase 3 User Flow 8.4.
 *
 * This table is distinct from audit_logs itself - audit_logs is what
 * gets exported; audit_exports is the metadata *about* an export
 * operation, and is not immutable in the same way (a job's status
 * legitimately transitions from pending -> processing -> completed).
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase8_API_Design.md Section 8
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_exports', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('requested_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->jsonb('filters'); // {date_from, date_to, resource_types: [...]}
            $table->string('file_path', 500)->nullable();
            $table->unsignedInteger('record_count')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestampTz('completed_at')->nullable();

            $table->timestampsTz();

            $table->index(['tenant_id', 'requested_by']);
            $table->index(['tenant_id', 'status']);
        });
        TenantRls::enable('audit_exports');
    }

    public function down(): void
    {
        TenantRls::disable('audit_exports');
        Schema::dropIfExists('audit_exports');
    }
};
