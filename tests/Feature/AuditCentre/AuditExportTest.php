<?php

declare(strict_types=1);

namespace Tests\Feature\AuditCentre;

use App\Modules\AuditCentre\Jobs\GenerateAuditExportPackage;
use App\Modules\AuditCentre\Services\AuditExportService;
use App\Modules\Employee\Models\User;
use App\Modules\Organisation\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AuditExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_requesting_an_export_creates_a_pending_record_and_dispatches_the_generation_job(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        $auditor = User::factory()->for($tenant)->create();

        $export = app(AuditExportService::class)->requestExport($auditor, ['date_from' => '2026-01-01']);

        $this->assertSame('pending', $export->status);
        Queue::assertPushed(GenerateAuditExportPackage::class);

        // The export request itself is logged (Phase 3 User Flow 8.4's
        // "tamper-evident metadata" requirement starts at request time,
        // not just at completion).
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'audit_export.requested',
            'resource_id' => $export->id,
        ]);
    }

    public function test_completed_export_generation_logs_its_own_completion_with_record_count(): void
    {
        Storage::fake('private');

        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        $auditor = User::factory()->for($tenant)->create();

        // Seed a couple of unrelated audit_log rows to be captured by the export.
        DB::table('audit_logs')->insert([
            ['id' => \Illuminate\Support\Str::uuid(), 'tenant_id' => $tenant->id, 'actor_user_id' => $auditor->id, 'action' => 'auth.login_succeeded', 'resource_type' => 'user', 'resource_id' => $auditor->id, 'occurred_at' => now(), 'created_at' => now()],
            ['id' => \Illuminate\Support\Str::uuid(), 'tenant_id' => $tenant->id, 'actor_user_id' => $auditor->id, 'action' => 'employee.created', 'resource_type' => 'user', 'resource_id' => $auditor->id, 'occurred_at' => now(), 'created_at' => now()],
        ]);

        $export = app(AuditExportService::class)->requestExport($auditor, []);

        (new GenerateAuditExportPackage($export->id))->handle(
            app(\App\Modules\AuditCentre\Services\AuditLogQueryService::class),
            app(\App\Support\AuditLogger::class),
        );

        $export->refresh();
        $this->assertSame('completed', $export->status);
        $this->assertGreaterThanOrEqual(2, $export->record_count);

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'audit_export.completed',
            'resource_id' => $export->id,
        ]);
    }
}
