<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Modules\Employee\Models\User;
use App\Modules\LMS\Models\Course;
use App\Modules\Organisation\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The existing TenantIsolationTest (Epic E1) proves isolation at the
 * Eloquent/RLS layer directly. This is the missing complementary
 * system-level test: a fully authenticated HTTP request from a Tenant A
 * user, attempting to fetch a Tenant B resource by its real, valid UUID.
 * This is the actual attack shape worth defending against (an
 * authenticated user of one tenant enumerating or guessing another
 * tenant's resource IDs), and it's a meaningfully different test than
 * "does the ORM scope correctly" - it also exercises SetTenantContext
 * middleware, session resolution, and the full request pipeline together.
 */
final class CrossTenantHttpAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_cannot_fetch_another_tenants_course_by_id(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        TenantContext::set($tenantB->id);
        $tenantBCourse = Course::factory()->for($tenantB)->create();

        TenantContext::set($tenantA->id);
        $tenantAUser = User::factory()->for($tenantA)->create();

        $response = $this->actingAs($tenantAUser)->getJson("/api/v1/courses/{$tenantBCourse->id}");

        // findOrFail() combined with the tenant scope means this resolves
        // as "not found," not "forbidden" - from Tenant A's perspective,
        // a Tenant B resource should not appear to exist at all, which is
        // itself a deliberate information-disclosure choice: a 403 would
        // confirm the ID is valid and belongs to *someone*; a 404 reveals
        // nothing.
        $response->assertStatus(404);
    }

    public function test_an_authenticated_user_cannot_list_another_tenants_employees(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        TenantContext::set($tenantB->id);
        $tenantBEmployee = User::factory()->for($tenantB)->create();

        TenantContext::set($tenantA->id);
        $tenantAUser = User::factory()->for($tenantA)->create();

        $response = $this->actingAs($tenantAUser)->getJson('/api/v1/employees');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertFalse($ids->contains($tenantBEmployee->id));
    }
}
