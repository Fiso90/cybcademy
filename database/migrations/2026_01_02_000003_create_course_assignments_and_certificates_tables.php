<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

require_once __DIR__ . '/2026_01_01_000002_create_rls_helper_function.php';

/**
 * Creates `course_assignments` (who must take what, and their progress)
 * and `certificates` (issued proof of completion).
 *
 * `certificates.certificate_uid` is globally unique (not per-tenant)
 * because it backs the public, unauthenticated verification endpoint
 * specified in Phase 8 Section 7 (`GET /certificates/verify/{uid}`) - a
 * third party verifying a certificate has no tenant context to supply.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase5_Database_Design.md Section 3.5
 * @see /mnt/user-data/outputs/CybCademy_Phase8_API_Design.md Section 7
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_assignments', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('due_date')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->enum('status', ['assigned', 'in_progress', 'completed', 'overdue'])
                ->default('assigned');

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['tenant_id', 'course_id', 'user_id']);
            $table->index(['tenant_id', 'user_id', 'status']);
        });
        TenantRls::enable('course_assignments');

        Schema::create('certificates', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('certificate_uid', 40)->unique(); // globally unique - see docblock
            $table->timestampTz('issued_at');
            $table->string('file_path', 500); // S3/R2 key

            $table->timestampsTz();

            $table->index(['tenant_id', 'user_id']);
        });
        TenantRls::enable('certificates');
    }

    public function down(): void
    {
        TenantRls::disable('certificates');
        TenantRls::disable('course_assignments');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('course_assignments');
    }
};
