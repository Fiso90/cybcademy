<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

require_once __DIR__ . '/2026_01_01_000002_create_rls_helper_function.php';

/**
 * Creates `human_risk_scores`.
 *
 * Time-series/append-only by design (Phase 5 Section 3.9): a new row per
 * calculation cycle, never an UPDATE, so the Executive Dashboard can chart
 * a genuine trend rather than only ever seeing the latest value.
 *
 * Phase 5's Risks section flagged this table as a partitioning candidate
 * before it grows large in production - not implemented in this
 * migration (out of scope for the initial schema), but the tenant_id +
 * calculated_at column pairing here is deliberately shaped to make a
 * future PARTITION BY RANGE (calculated_at) migration straightforward to
 * bolt on without a model/application-layer change.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase5_Database_Design.md Section 3.9
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('human_risk_scores', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('department_id')->nullable()->constrained('departments')->cascadeOnDelete();
            $table->decimal('score', 5, 2);
            $table->timestampTz('calculated_at');
            $table->jsonb('contributing_factors'); // {training: .., assessment: .., phishing: ..} weighting breakdown
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['tenant_id', 'user_id', 'calculated_at']);
            $table->index(['tenant_id', 'department_id', 'calculated_at']);
        });

        // Exactly one of user_id / department_id must be set - a row is
        // either an individual score or a department aggregate, never
        // both and never neither. Enforced at the database level (via a
        // raw statement, since Blueprint has no portable check()
        // builder method), not just by convention in the calculation
        // service.
        DB::statement(<<<'SQL'
            ALTER TABLE human_risk_scores
            ADD CONSTRAINT human_risk_scores_exactly_one_subject CHECK (
                (user_id IS NOT NULL)::int + (department_id IS NOT NULL)::int = 1
            );
        SQL);

        TenantRls::enable('human_risk_scores');
    }

    public function down(): void
    {
        TenantRls::disable('human_risk_scores');
        Schema::dropIfExists('human_risk_scores');
    }
};
