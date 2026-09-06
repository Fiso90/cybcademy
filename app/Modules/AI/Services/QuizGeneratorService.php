<?php

declare(strict_types=1);

namespace App\Modules\AI\Services;

use App\Modules\LMS\Models\Course;
use App\Modules\LMS\Models\QuestionBankItem;
use App\Support\TenantContext;
use Illuminate\Support\Collection;

/**
 * FR-9.2: AI Quiz Generator. Generates draft question_bank items from a
 * course's lesson content - draft, because generated questions are never
 * auto-published into a live assessment without a Trainer reviewing them
 * first (a hallucinated "correct answer" reaching an actual employee
 * assessment would be a real problem, not a cosmetic one, given
 * assessment results feed the Human Risk Score - Epic E6).
 */
final class QuizGeneratorService
{
    public function __construct(
        private readonly AiGatewayService $ai,
    ) {
    }

    /**
     * @return Collection<QuestionBankItem> newly created, unreviewed items
     */
    public function generateForCourse(Course $course, int $questionCount = 5): Collection
    {
        $lessonContent = $course->lessons
            ->pluck('content_body')
            ->filter()
            ->implode("\n\n");

        if (trim($lessonContent) === '') {
            throw new \DomainException('Cannot generate quiz questions: this course has no text lesson content to draw from.');
        }

        $systemPrompt = <<<'PROMPT'
            You are generating cybersecurity awareness training quiz questions.
            Respond ONLY with a JSON array, no other text. Each element must have:
            "question_text" (string), "question_type" ("mcq" or "true_false"),
            "options" (array of {"id": string, "text": string} for mcq, omit for true_false),
            "correct_answer" ({"option_id": string} for mcq, {"value": true|false} for true_false).
            PROMPT;

        $userPrompt = "Generate {$questionCount} quiz questions based on this training "
            . "content:\n\n{$lessonContent}";

        $rawJson = $this->ai->generate('quiz_generator', $systemPrompt, $userPrompt, maxTokens: 2048);
        $parsed = json_decode($rawJson, associative: true);

        if (! is_array($parsed)) {
            throw new \RuntimeException('AI quiz generation did not return valid JSON.');
        }

        return collect($parsed)->map(fn (array $q) => QuestionBankItem::create([
            'tenant_id' => TenantContext::current(),
            'question_text' => $q['question_text'],
            'question_type' => $q['question_type'],
            'options' => $q['options'] ?? null,
            'correct_answer' => $q['correct_answer'],
        ]));
        // Note: these items are created but NOT attached to any
        // assessment (assessment_questions pivot, Epic E2) - attaching is
        // a separate, explicit Trainer action, which is the review gate
        // referenced above.
    }
}
