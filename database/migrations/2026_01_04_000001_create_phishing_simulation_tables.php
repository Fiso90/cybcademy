<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

require_once __DIR__ . '/2026_01_01_000002_create_rls_helper_function.php';

/**
 * Creates `phishing_templates`, `phishing_campaigns`, `phishing_results`.
 *
 * `phishing_templates.tenant_id` is nullable by design: platform-provided
 * templates (tenant_id = null) are shared across all tenants, while
 * tenant-authored or AI-generated ones are scoped to their tenant. RLS on
 * a nullable tenant_id column is handled explicitly below (a naive policy
 * of `tenant_id = current_tenant` would hide the platform templates from
 * everyone, which is wrong) - the policy is written to also allow rows
 * where tenant_id IS NULL.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase5_Database_Design.md Section 3.7
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phishing_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->string('subject', 255);
            $table->longText('body');
            $table->boolean('ai_generated')->default(false);

            $table->timestampsTz();
            $table->softDeletesTz();
        });

        // Custom RLS policy (not the standard TenantRls::enable helper)
        // because platform-provided templates (tenant_id IS NULL) must
        // remain visible to every tenant, not just their author.
        DB::statement('ALTER TABLE phishing_templates ENABLE ROW LEVEL SECURITY;');
        DB::statement('ALTER TABLE phishing_templates FORCE ROW LEVEL SECURITY;');
        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation_phishing_templates ON phishing_templates
                USING (
                    tenant_id IS NULL
                    OR tenant_id = NULLIF(current_setting('app.current_tenant', true), '')::uuid
                )
                WITH CHECK (
                    tenant_id = NULLIF(current_setting('app.current_tenant', true), '')::uuid
                );
        SQL);
        // Note the asymmetry: USING allows reading platform templates
        // (tenant_id IS NULL), but WITH CHECK requires an actual tenant_id
        // on INSERT/UPDATE - an ordinary tenant user can never author a
        // platform-wide template through the application, only Solunar's
        // internal seeding process can (via a privileged connection that
        // bypasses RLS entirely, documented in Phase 7 Section 7's
        // break-glass procedure).

        Schema::create('phishing_campaigns', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 255);
            $table->foreignUuid('template_id')->constrained('phishing_templates')->restrictOnDelete();
            $table->jsonb('target_scope'); // e.g. {"department_ids":[...]} or {"role_slugs":[...]} or {"user_ids":[...]}
            $table->timestampTz('scheduled_at')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'sent', 'completed'])->default('draft');
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['tenant_id', 'status']);
        });
        TenantRls::enable('phishing_campaigns');

        Schema::create('phishing_results', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('campaign_id')->constrained('phishing_campaigns')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('event_type', ['sent', 'opened', 'clicked', 'submitted_credentials', 'reported']);
            $table->timestampTz('event_at');
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['tenant_id', 'campaign_id', 'user_id']);
            $table->index(['tenant_id', 'campaign_id', 'event_type']);
        });
        TenantRls::enable('phishing_results');
    }

    public function down(): void
    {
        TenantRls::disable('phishing_results');
        TenantRls::disable('phishing_campaigns');
        DB::statement('DROP POLICY IF EXISTS tenant_isolation_phishing_templates ON phishing_templates;');
        DB::statement('ALTER TABLE phishing_templates DISABLE ROW LEVEL SECURITY;');
        Schema::dropIfExists('phishing_results');
        Schema::dropIfExists('phishing_campaigns');
        Schema::dropIfExists('phishing_templates');
    }
};
