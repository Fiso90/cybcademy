<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

require_once __DIR__ . '/2026_01_01_000002_create_rls_helper_function.php';

/**
 * Creates `subscriptions` and `notifications`.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase5_Database_Design.md Section 3.11
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->enum('tier', ['basic', 'professional', 'enterprise']);
            $table->unsignedInteger('seats_licensed');
            $table->enum('billing_cycle', ['monthly', 'annual']);
            $table->enum('status', ['active', 'past_due', 'cancelled', 'trialing'])->default('trialing');
            $table->timestampTz('current_period_start');
            $table->timestampTz('current_period_end');

            $table->timestampsTz();

            $table->index(['tenant_id', 'status']);
        });
        TenantRls::enable('subscriptions');

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 100); // e.g. "course_assigned", "policy_reminder", "incident_assigned"
            $table->jsonb('payload');
            $table->timestampTz('read_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['tenant_id', 'user_id', 'read_at']);
        });
        TenantRls::enable('notifications');
    }

    public function down(): void
    {
        TenantRls::disable('notifications');
        TenantRls::disable('subscriptions');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('subscriptions');
    }
};
