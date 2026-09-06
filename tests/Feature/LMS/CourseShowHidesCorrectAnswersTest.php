<?php

declare(strict_types=1);

namespace Tests\Feature\LMS;

use App\Modules\Employee\Models\User;
use App\Modules\LMS\Models\Assessment;
use App\Modules\LMS\Models\Course;
use App\Modules\LMS\Models\QuestionBankItem;
use App\Modules\Organisation\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Locks in the Phase 11 fix to CourseController::show(): correct_answer
 * must never appear in the JSON response an employee's Course Player
 * fetches, even though the underlying Eloquent relation
 * (Assessment::questions()) does eager-load it. This is the concrete
 * enforcement of the guarantee AssessmentService's own docblock has
 * claimed since Epic E2 - this test is what makes that claim actually
 * true end-to-end, not just true of the grading code path.
 */
final class CourseShowHidesCorrectAnswersTest extends TestCase
{
    use RefreshDatabase;

    public function test_correct_answer_never_appears_in_the_course_show_response(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        $user = User::factory()->for($tenant)->create();
        $this->actingAs($user);

        $course = Course::factory()->for($tenant)->create(['status' => 'published']);
        $question = QuestionBankItem::factory()->for($tenant)->create([
            'question_type' => 'true_false',
            'correct_answer' => ['value' => true],
        ]);
        $assessment = Assessment::factory()->for($tenant)->create(['course_id' => $course->id]);
        $assessment->questions()->attach($question->id, ['sequence_order' => 1]);

        $response = $this->getJson("/api/v1/courses/{$course->id}");

        $response->assertJsonMissingPath('data.assessments.0.questions.0.correct_answer');

        // Belt-and-braces: also confirm the raw response body string
        // doesn't contain the literal answer value, in case the key were
        // renamed rather than removed.
        $response->assertDontSee('"value":true', false);
    }
}
