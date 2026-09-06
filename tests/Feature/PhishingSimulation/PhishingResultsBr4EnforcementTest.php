<?php

declare(strict_types=1);

namespace Tests\Feature\PhishingSimulation;

use App\Modules\Auth\Models\Role;
use App\Modules\Employee\Models\User;
use App\Modules\Organisation\Models\Department;
use App\Modules\Organisation\Models\Tenant;
use App\Modules\PhishingSimulation\Models\PhishingCampaign;
use App\Modules\PhishingSimulation\Models\PhishingResult;
use App\Modules\PhishingSimulation\Models\PhishingTemplate;
use App\Modules\PhishingSimulation\Repositories\PhishingResultsRepository;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Proves BR-4 holds: a Manager (non-privileged role) requesting phishing
 * results receives department-aggregated data with no way to identify an
 * individual employee's outcome, while a Security Officer receives full
 * individual-level data. This is the most consequential business rule in
 * Epic E4 - see PhishingResultsRepository's docblock for why it is
 * enforced in exactly one place.
 */
final class PhishingResultsBr4EnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_role_receives_aggregated_results_with_no_individual_user_data(): void
    {
        [$campaign, $employee] = $this->createCampaignWithOneClickedResult();
        $manager = $this->makeUserWithRole('manager');

        $results = app(PhishingResultsRepository::class)->resultsForRequester($campaign, $manager);

        // Aggregated shape: rows are grouped by department_id + event_type
        // with a count - there is no user_id or user name anywhere in
        // this result set for a Manager to read.
        $this->assertGreaterThan(0, $results->count());
        foreach ($results as $row) {
            $this->assertObjectNotHasProperty('user_id', $row);
            $this->assertObjectHasProperty('department_id', $row);
            $this->assertObjectHasProperty('event_count', $row);
        }
    }

    public function test_security_officer_role_receives_individual_level_results(): void
    {
        [$campaign, $employee] = $this->createCampaignWithOneClickedResult();
        $officer = $this->makeUserWithRole('security-officer');

        $results = app(PhishingResultsRepository::class)->resultsForRequester($campaign, $officer);

        $this->assertSame(1, $results->count());
        $this->assertSame($employee->id, $results->first()->user_id);
    }

    /**
     * @return array{0: PhishingCampaign, 1: User}
     */
    private function createCampaignWithOneClickedResult(): array
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $department = Department::factory()->for($tenant)->create();
        $employee = User::factory()->for($tenant)->create(['department_id' => $department->id]);
        $creator = User::factory()->for($tenant)->create();

        $template = PhishingTemplate::factory()->create(['tenant_id' => $tenant->id]);

        $campaign = PhishingCampaign::create([
            'tenant_id' => $tenant->id,
            'name' => 'Q3 Finance Phishing Drill',
            'template_id' => $template->id,
            'target_scope' => ['user_ids' => [$employee->id]],
            'status' => 'sent',
            'created_by' => $creator->id,
        ]);

        PhishingResult::create([
            'tenant_id' => $tenant->id,
            'campaign_id' => $campaign->id,
            'user_id' => $employee->id,
            'event_type' => 'clicked',
            'event_at' => now(),
            'created_at' => now(),
        ]);

        return [$campaign, $employee];
    }

    private function makeUserWithRole(string $slug): User
    {
        $tenant = Tenant::where('id', TenantContext::current())->first();
        $user = User::factory()->for($tenant)->create();
        $role = Role::create(['tenant_id' => $tenant->id, 'name' => ucfirst(str_replace('-', ' ', $slug)), 'slug' => $slug]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id, 'model_type' => 'user']);

        return $user;
    }
}
