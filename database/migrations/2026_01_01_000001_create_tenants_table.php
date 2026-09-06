<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `tenants` table.
 *
 * This is the tenant boundary itself and is therefore the one table in the
 * schema that is NOT tenant-scoped and does NOT carry Row-Level Security
 * (there is nothing to scope it against). Every other tenant-scoped table
 * references tenants.id via a `tenant_id` foreign key.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase5_Database_Design.md Section 3.1
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 255);
            $table->string('industry', 100)->nullable();
            $table->enum('subscription_tier', ['basic', 'professional', 'enterprise'])
                ->default('basic');

            // Configurable compliance framework mapping (POPIA, NDPA, etc.)
            // per Phase 3 Section 12 (Compliance Requirements).
            $table->jsonb('compliance_frameworks')->default('[]');

            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
