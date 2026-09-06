<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\Modules\AI\Services\AiGatewayService;
use App\Modules\AI\Services\QuizGeneratorService;
use App\Modules\Employee\Models\User;
use App\Modules\LMS\Models\Course;
use App\Modules\LMS\Models\Lesson;
use App\Modules\Organisation\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class AiFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_gateway_rejects_a_prompt_containing_an_email_address_before_it_reaches_the_ai_provider(): void
    {
        Http::fake(); // if this test fails, it will be because a real HTTP call was attempted

        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/email address/');

        app(AiGatewayService::class)->generate(
            'quiz_generator',
            'system prompt',
            'Please write a question about jane.doe@example.com clicking a link.',
        );

        Http::assertNothingSent();
    }

    public function test_ai_generated_quiz_questions_are_never_automatically_attached_to_an_assessment(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [[
                    'type' => 'text',
                    'text' => json_encode([[
                        'question_text' => 'Should you click links from unknown senders?',
                        'question_type' => 'true_false',
                        'correct_answer' => ['value' => false],
                    ]]),
                ]],
                'usage' => ['input_tokens' => 100, 'output_tokens' => 50],
            ], 200),
        ]);

        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        $user = User::factory()->for($tenant)->create();
        $this->actingAs($user);

        $course = Course::factory()->for($tenant)->create();
        Lesson::factory()->for($course)->create(['content_body' => 'Never click unknown links.', 'content_type' => 'text']);

        $questions = app(QuizGeneratorService::class)->generateForCourse($course->fresh(['lessons']));

        $this->assertCount(1, $questions);

        // The generated question exists in question_bank...
        $this->assertDatabaseHas('question_bank', ['id' => $questions->first()->id]);

        // ...but is NOT attached to any assessment - attaching is a
        // separate, explicit Trainer review action per the service's
        // docblock, and this test locks that invariant in.
        $this->assertDatabaseMissing('assessment_questions', ['question_id' => $questions->first()->id]);
    }
}
