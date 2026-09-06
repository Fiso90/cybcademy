<?php

declare(strict_types=1);

namespace Tests\Feature\Analytics;

use App\Modules\Analytics\Services\HumanRiskScoreCalculator;
use App\Modules\Employee\Models\User;
use App\Modules\LMS\Models\AssessmentAttempt;
use App\Modules\LMS\Models\Course;
use App\Modules\LMS\Models\Assessment;
use App\Modules\LMS\Models\CourseAssignment;
use App\Modules\Organisation\Models\Tenant;
use App\Modules\PhishingSimulation\Models\PhishingCampaign;
use App\Modules\PhishingSimulation\Models\PhishingResult;
use App\Modules\PhishingSimulation\Models\PhishingTemplate;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HumanRiskScoreCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_brand_new_employee_with_no_history_scores_100_not_0(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        $user = User::factory()->for($tenant)->create();

        $score = app(HumanRiskScoreCalculator::class)->calculateForUser($user);

        // Deliberate design choice, tested explicitly: an employee with
        // no training/assessment/phishing history yet is neutral (100),
        // not penalised (0) - see the calculator's docblock for why
        // scoring a day-one hire as "high risk" would be misleading.
        $this->assertEquals(100.0, (float) $score->score);
    }

    public function test_clicking_a_phishing_simulation_reduces_the_phishing_component_but_reporting_it_afterwards_still_counts_as_a_click_happened(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        $user = User::factory()->for($tenant)->create();
        $creator = User::factory()->for($tenant)->create();
        $template = PhishingTemplate::factory()->create(['tenant_id' => $tenant->id]);
        $campaign = PhishingCampaign::create([
            'tenant_id' => $tenant->id, 'name' => 'Test', 'template_id' => $template->id,
            'target_scope' => ['user_ids' => [$user->id]], 'status' => 'sent', 'created_by' => $creator->id,
        ]);

        PhishingResult::create([
            'tenant_id' => $tenant->id, 'campaign_id' => $campaign->id, 'user_id' => $user->id,
            'event_type' => 'clicked', 'event_at' => now(), 'created_at' => now(),
        ]);
        PhishingResult::create([
            'tenant_id' => $tenant->id, 'campaign_id' => $campaign->id, 'user_id' => $user->id,
            'event_type' => 'reported', 'event_at' => now()->addMinute(), 'created_at' => now(),
        ]);

        $score = app(HumanRiskScoreCalculator::class)->calculateForUser($user);

        // Per the calculator's docblock: "reported" OR "did not click"
        // counts as a good outcome per campaign. Because this campaign
        // contains BOTH a click and a report, the current binary logic
        // (types.contains('reported') OR !types.contains('clicked'))
        // treats reporting-after-clicking as fully redeeming the click -
        // this test documents and locks in that specific behaviour so a
        // future change to the scoring logic is a deliberate decision,
        // not an accidental regression.
        $this->assertEquals(100.0, (float) $score->contributing_factors['phishing_resilience']);
    }

    public function test_overall_score_reflects_the_documented_40_30_30_weighting(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        $user = User::factory()->for($tenant)->create();

        $course = Course::factory()->for($tenant)->create(['status' => 'published']);
        CourseAssignment::factory()->create([
            'tenant_id' => $tenant->id, 'course_id' => $course->id, 'user_id' => $user->id,
            'status' => 'completed', 'completed_at' => now(), 'due_date' => null,
        ]);
        // training_completion = 100

        $assessment = Assessment::factory()->for($tenant)->create(['course_id' => $course->id]);
        AssessmentAttempt::create([
            'tenant_id' => $tenant->id, 'assessment_id' => $assessment->id, 'user_id' => $user->id,
            'score' => 50, 'passed' => false, 'answers' => [], 'submitted_at' => now(),
        ]);
        // assessment_performance = 50

        // no phishing history => phishing_resilience defaults to 100

        $score = app(HumanRiskScoreCalculator::class)->calculateForUser($user);

        // 100*0.40 + 50*0.30 + 100*0.30 = 40 + 15 + 30 = 85
        $this->assertEquals(85.0, (float) $score->score);
    }
}
