<?php

declare(strict_types=1);

namespace Tests\Feature\Policy;

use App\Modules\Employee\Models\User;
use App\Modules\Organisation\Models\Tenant;
use App\Modules\Policy\Models\Policy;
use App\Modules\Policy\Services\PolicyAcknowledgementService;
use App\Modules\Policy\Services\PolicyService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Covers BR-3: policy acknowledgement records are immutable once
 * submitted; corrections require a new versioned acknowledgement, not
 * an edit.
 */
final class PolicyAcknowledgementImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_acknowledging_the_same_policy_version_twice_is_rejected_not_overwritten(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $user = User::factory()->for($tenant)->create();
        $policyService = app(PolicyService::class);
        $ackService = app(PolicyAcknowledgementService::class);

        $policy = $policyService->uploadNewVersion('Acceptable Use Policy', 'path/to/file.pdf', $user->id);
        $policyService->publish($policy->id, $user->id);

        $first = $ackService->acknowledge($policy, $user, '203.0.113.10');

        $this->expectException(ValidationException::class);
        $ackService->acknowledge($policy, $user, '203.0.113.10');

        $this->assertSame(1, $policy->acknowledgements()->count());
    }

    public function test_correcting_an_acknowledgement_requires_a_new_policy_version_not_an_edit(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $user = User::factory()->for($tenant)->create();
        $policyService = app(PolicyService::class);
        $ackService = app(PolicyAcknowledgementService::class);

        $v1 = $policyService->uploadNewVersion('Data Handling Policy', 'v1.pdf', $user->id);
        $policyService->publish($v1->id, $user->id);
        $ackService->acknowledge($v1, $user, '203.0.113.10');

        $v2 = $policyService->uploadNewVersion('Data Handling Policy', 'v2.pdf', $user->id);
        $this->assertSame(2, $v2->version);
        $policyService->publish($v2->id, $user->id);

        // The v1 acknowledgement still exists, untouched - a new,
        // separate row is created for v2 rather than mutating it.
        $v2Ack = $ackService->acknowledge($v2, $user, '203.0.113.11');

        $this->assertSame(1, $v1->acknowledgements()->count());
        $this->assertSame(1, $v2->acknowledgements()->count());
        $this->assertNotSame($v2Ack->id, $v1->acknowledgements()->first()->id);
    }
}
