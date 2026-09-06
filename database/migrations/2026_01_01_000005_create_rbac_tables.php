<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

require_once __DIR__ . '/2026_01_01_000002_create_rls_helper_function.php';

/**
 * Creates the RBAC schema (roles, permissions, and pivots), aligned with the
 * spatie/laravel-permission package conventions referenced in Phase 4/5.
 *
 * Roles are tenant-scoped (each tenant gets its own row for, e.g.,
 * "Organisation Administrator"). Permissions are global - the permission
 * catalogue is fixed by the platform and not customer-editable.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase5_Database_Design.md Section 3.3
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 150)->unique(); // e.g. "employees.manage", "audit.export"
            $table->string('description', 255)->nullable();
            $table->timestampsTz();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 100); // e.g. "Organisation Administrator"
            $table->string('slug', 100); // e.g. "org-admin" - matches the 11 defined platform roles
            $table->timestampsTz();

            $table->unique(['tenant_id', 'slug']);
        });
        TenantRls::enable('roles');

        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignUuid('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->uuid('model_id'); // typically users.id
            $table->string('model_type', 100)->default('user');
            $table->primary(['role_id', 'model_id', 'model_type']);
            $table->index(['tenant_id', 'model_id', 'model_type']);
        });
        TenantRls::enable('model_has_roles');
    }

    public function down(): void
    {
        TenantRls::disable('model_has_roles');
        TenantRls::disable('roles');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
