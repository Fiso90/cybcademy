<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Modules\Billing\Models\Subscription;
use App\Modules\Billing\Services\TierGateService;
use App\Modules\Organisation\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TierGateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_basic_tier_tenant_cannot_use_an_enterprise_only_ai_feature(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        Subscription::create([
            'tenant_id' => $tenant->id, 'tier' => 'basic', 'seats_licensed' => 50,
            'billing_cycle' => 'monthly', 'status' => 'active',
            'current_period_start' => now(), 'current_period_end' => now()->addMonth(),
        ]);

        $this->assertFalse(app(TierGateService::class)->tenantCanUse('ai_executive_reports'));
    }

    public function test_an_enterprise_tier_tenant_can_use_every_gated_feature(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        Subscription::create([
            'tenant_id' => $tenant->id, 'tier' => 'enterprise', 'seats_licensed' => 500,
            'billing_cycle' => 'annual', 'status' => 'active',
            'current_period_start' => now(), 'current_period_end' => now()->addYear(),
        ]);

        $gate = app(TierGateService::class);
        $this->assertTrue($gate->tenantCanUse('ai_executive_reports'));
        $this->assertTrue($gate->tenantCanUse('ai_phishing_generator'));
        $this->assertTrue($gate->tenantCanUse('audit_centre'));
    }

    public function test_a_tenant_with_no_active_subscription_cannot_use_any_gated_feature(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        // No subscription row created at all - the "gap" scenario from
        // the Epic E9 README before this service existed.

        $this->assertFalse(app(TierGateService::class)->tenantCanUse('ai_quiz_generator'));
    }
}
