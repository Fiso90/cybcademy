<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

require_once __DIR__ . '/2026_01_01_000002_create_rls_helper_function.php';

/**
 * Creates `courses` and `lessons` — the Course Builder / Course Player
 * content model.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase5_Database_Design.md Section 3.5
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['tenant_id', 'status']);
        });
        TenantRls::enable('courses');

        Schema::create('lessons', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('title', 255);
            $table->enum('content_type', ['video', 'text', 'interactive']);
            $table->string('content_url', 500)->nullable();
            $table->longText('content_body')->nullable(); // for content_type = text
            $table->unsignedInteger('sequence_order')->default(0);

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['tenant_id', 'course_id', 'sequence_order']);
        });
        TenantRls::enable('lessons');
    }

    public function down(): void
    {
        TenantRls::disable('lessons');
        TenantRls::disable('courses');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('courses');
    }
};
