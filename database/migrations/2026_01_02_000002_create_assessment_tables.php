<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

require_once __DIR__ . '/2026_01_01_000002_create_rls_helper_function.php';

/**
 * Creates the assessment domain: `question_bank`, `assessments`,
 * `assessment_questions` (pivot), `assessment_attempts`.
 *
 * `assessment_questions` deliberately has no tenant_id of its own -
 * per Phase 5 Section 3.5, it is scoped transitively via assessment_id,
 * since both sides of the pivot (assessments, question_bank) are already
 * tenant-scoped and a pivot row cannot exist connecting two different
 * tenants' rows in practice (enforced by application-layer validation
 * when questions are attached, not by an RLS policy on the pivot itself).
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase5_Database_Design.md Section 3.5
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_bank', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->text('question_text');
            $table->enum('question_type', ['mcq', 'true_false', 'scenario']);
            $table->jsonb('options')->nullable(); // e.g. [{"id":"a","text":"..."}]
            $table->jsonb('correct_answer'); // e.g. {"option_id":"a"} or {"value":true}

            $table->timestampsTz();
            $table->softDeletesTz();
        });
        TenantRls::enable('question_bank');

        Schema::create('assessments', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('title', 255);
            $table->unsignedTinyInteger('passing_score'); // percentage, 0-100

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['tenant_id', 'course_id']);
        });
        TenantRls::enable('assessments');

        Schema::create('assessment_questions', function (Blueprint $table): void {
            $table->foreignUuid('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->foreignUuid('question_id')->constrained('question_bank')->cascadeOnDelete();
            $table->unsignedInteger('sequence_order')->default(0);
            $table->primary(['assessment_id', 'question_id']);
        });

        Schema::create('assessment_attempts', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('score'); // percentage achieved
            $table->boolean('passed');
            $table->jsonb('answers'); // submitted answers, for review/dispute purposes
            $table->timestampTz('submitted_at');

            $table->timestampsTz();

            $table->index(['tenant_id', 'assessment_id', 'user_id']);
        });
        TenantRls::enable('assessment_attempts');
    }

    public function down(): void
    {
        TenantRls::disable('assessment_attempts');
        TenantRls::disable('assessments');
        TenantRls::disable('question_bank');
        Schema::dropIfExists('assessment_attempts');
        Schema::dropIfExists('assessment_questions');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('question_bank');
    }
};
