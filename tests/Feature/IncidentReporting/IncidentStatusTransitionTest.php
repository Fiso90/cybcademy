<?php

declare(strict_types=1);

namespace Tests\Feature\IncidentReporting;

use App\Modules\Employee\Models\User;
use App\Modules\IncidentReporting\Services\IncidentService;
use App\Modules\Organisation\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class IncidentStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_open_incident_cannot_jump_directly_to_closed(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $reporter = User::factory()->for($tenant)->create();
        $officer = User::factory()->for($tenant)->create();
        $service = app(IncidentService::class);

        $incident = $service->submit($reporter, 'phishing', 'Suspicious email received.');

        $this->assertSame('open', $incident->status);

        $this->expectException(\DomainException::class);
        $service->updateStatus($incident->id, 'closed', null, $officer->id);
    }

    public function test_the_valid_open_to_in_progress_to_resolved_path_succeeds(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $reporter = User::factory()->for($tenant)->create();
        $officer = User::factory()->for($tenant)->create();
        $service = app(IncidentService::class);

        $incident = $service->submit($reporter, 'suspicious_activity', 'Unusual login pattern.');

        $incident = $service->updateStatus($incident->id, 'in_progress', $officer->id, $officer->id);
        $this->assertSame('in_progress', $incident->status);
        $this->assertSame($officer->id, $incident->assigned_to);

        $incident = $service->updateStatus($incident->id, 'resolved', null, $officer->id);
        $this->assertSame('resolved', $incident->status);
        $this->assertNotNull($incident->resolved_at);
    }
}
