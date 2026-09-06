<?php

declare(strict_types=1);

namespace Tests\Feature\LMS;

use App\Modules\Employee\Models\User;
use App\Modules\LMS\Models\Assessment;
use App\Modules\LMS\Models\Certificate;
use App\Modules\LMS\Models\Course;
use App\Modules\LMS\Models\CourseAssignment;
use App\Modules\LMS\Models\QuestionBankItem;
use App\Modules\LMS\Services\AssessmentService;
use App\Modules\Organisation\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the highest-risk business logic in Epic E2: grading correctness,
 * the pass -> certificate-issuance trigger, and idempotency of that
 * trigger (per Phase 10 CertificateService docblock).
 */
final class AssessmentGradingTest extends TestCase
{
    use RefreshDatabase;

    public function test_passing_score_issues_a_certificate_and_completes_the_course_assignment(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $user = User::factory()->for($tenant)->create();
        $course = Course::factory()->for($tenant)->create(['status' => 'published']);
        CourseAssignment::factory()->create([
            'tenant_id' => $tenant->id, 'course_id' => $course->id, 'user_id' => $user->id, 'status' => 'assigned',
        ]);

        $question = QuestionBankItem::factory()->for($tenant)->create([
            'question_type' => 'true_false',
            'correct_answer' => ['value' => true],
        ]);

        $assessment = Assessment::factory()->for($tenant)->create([
            'course_id' => $course->id,
            'passing_score' => 80,
        ]);
        $assessment->questions()->attach($question->id, ['sequence_order' => 1]);

        $attempt = app(AssessmentService::class)->submitAttempt(
            $assessment,
            $user,
            [$question->id => true],
        );

        $this->assertTrue($attempt->passed);
        $this->assertSame(100, $attempt->score);

        $this->assertDatabaseHas('course_assignments', [
            'course_id' => $course->id,
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $this->assertSame(1, Certificate::where('user_id', $user->id)->where('course_id', $course->id)->count());
    }

    public function test_reattempting_after_already_passing_does_not_issue_a_duplicate_certificate(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $user = User::factory()->for($tenant)->create();
        $course = Course::factory()->for($tenant)->create(['status' => 'published']);
        $question = QuestionBankItem::factory()->for($tenant)->create([
            'question_type' => 'true_false',
            'correct_answer' => ['value' => true],
        ]);
        $assessment = Assessment::factory()->for($tenant)->create(['course_id' => $course->id, 'passing_score' => 50]);
        $assessment->questions()->attach($question->id, ['sequence_order' => 1]);

        $service = app(AssessmentService::class);
        $service->submitAttempt($assessment, $user, [$question->id => true]);
        $service->submitAttempt($assessment, $user, [$question->id => true]);

        $this->assertSame(1, Certificate::where('user_id', $user->id)->where('course_id', $course->id)->count());
    }
}
