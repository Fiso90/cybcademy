<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

require_once __DIR__ . '/2026_01_01_000002_create_rls_helper_function.php';

/**
 * Creates `knowledge_base_articles` (FR-10.4).
 *
 * Nullable tenant_id, same pattern as phishing_templates (Epic E4):
 * platform-authored help content (tenant_id IS NULL) is shared across all
 * tenants, while a tenant may optionally author its own internal articles
 * (e.g. "How we handle X internally") scoped to itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_base_articles', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->string('title', 255);
            $table->longText('body');
            $table->string('category', 100)->nullable();
            $table->boolean('published')->default(false);

            $table->timestampsTz();
            $table->softDeletesTz();
        });

        // Same asymmetric policy shape as phishing_templates (Epic E4) -
        // see that migration's docblock for the full rationale.
        DB::statement('ALTER TABLE knowledge_base_articles ENABLE ROW LEVEL SECURITY;');
        DB::statement('ALTER TABLE knowledge_base_articles FORCE ROW LEVEL SECURITY;');
        DB::statement(<<<'SQL'
            CREATE POLICY tenant_isolation_knowledge_base_articles ON knowledge_base_articles
                USING (
                    tenant_id IS NULL
                    OR tenant_id = NULLIF(current_setting('app.current_tenant', true), '')::uuid
                )
                WITH CHECK (
                    tenant_id = NULLIF(current_setting('app.current_tenant', true), '')::uuid
                );
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS tenant_isolation_knowledge_base_articles ON knowledge_base_articles;');
        Schema::dropIfExists('knowledge_base_articles');
    }
};
