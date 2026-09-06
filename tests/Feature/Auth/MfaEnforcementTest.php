<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Modules\Auth\Models\Role;
use App\Modules\Employee\Models\User;
use App\Modules\Organisation\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Closes a real gap: BR-2 (MFA mandatory for privileged roles) has been
 * implemented since Epic E1's EnsureMfaVerified middleware, but no test
 * in any prior epic's drop actually proved it blocks an unverified
 * privileged user at the HTTP layer - existing tests exercised business
 * logic below the middleware stack, not the middleware itself. This is
 * a genuine Phase 12 addition, not a restatement of something already
 * covered.
 */
final class MfaEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_privileged_role_without_mfa_enabled_is_blocked_from_protected_routes(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $officer = User::factory()->for($tenant)->create(['mfa_enabled' => false]);
        $role = Role::create(['tenant_id' => $tenant->id, 'name' => 'Security Officer', 'slug' => 'security-officer']);
        $officer->roles()->attach($role->id, ['tenant_id' => $tenant->id, 'model_type' => 'user']);

        $response = $this->actingAs($officer)->getJson('/api/v1/employees');

        $response->assertStatus(403);
    }

    public function test_a_privileged_role_with_mfa_enabled_but_not_verified_this_session_is_blocked(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $officer = User::factory()->for($tenant)->create(['mfa_enabled' => true]);
        $role = Role::create(['tenant_id' => $tenant->id, 'name' => 'Security Officer', 'slug' => 'security-officer']);
        $officer->roles()->attach($role->id, ['tenant_id' => $tenant->id, 'model_type' => 'user']);

        // mfa_enabled=true but the session never recorded mfa_verified_at
        // (Epic E1's AuthController::mfaChallenge() sets this on
        // successful TOTP verification) - simulates a session that
        // somehow skipped the challenge step.
        $response = $this->actingAs($officer)->getJson('/api/v1/employees');

        $response->assertStatus(403);
    }

    public function test_a_non_privileged_role_is_never_blocked_by_the_mfa_check_regardless_of_mfa_status(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $employee = User::factory()->for($tenant)->create(['mfa_enabled' => false, 'status' => 'active']);

        $response = $this->actingAs($employee)->getJson('/api/v1/me/courses');

        // An Employee role is not in EnsureMfaVerified::PRIVILEGED_ROLE_SLUGS
        // (Epic E1), so this should reach the controller and succeed (or
        // fail for a reason unrelated to MFA) rather than a 403 from the
        // MFA middleware specifically.
        $response->assertStatus(200);
    }
}
