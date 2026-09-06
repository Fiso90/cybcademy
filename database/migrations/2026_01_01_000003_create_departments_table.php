<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

require_once __DIR__ . '/2026_01_01_000002_create_rls_helper_function.php';

/**
 * Creates the `departments` table.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase5_Database_Design.md Section 3.4
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 255);
            $table->foreignUuid('parent_department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['tenant_id', 'parent_department_id']);
        });

        TenantRls::enable('departments');
    }

    public function down(): void
    {
        TenantRls::disable('departments');
        Schema::dropIfExists('departments');
    }
};
