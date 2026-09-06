<?php

declare(strict_types=1);

namespace App\Modules\LMS\Services;

use App\Modules\Employee\Models\User;
use App\Modules\LMS\Models\Assessment;
use App\Modules\LMS\Models\AssessmentAttempt;
use App\Modules\LMS\Models\CourseAssignment;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * Grading logic for assessment attempts (FR-3.3), and the trigger point
 * for certificate issuance (FR-3.4) and course_assignment completion
 * (which the Compliance/Executive dashboards, Epic E6, read from).
 *
 * Grading is deliberately server-side and answer keys
 * (question_bank.correct_answer) are never sent to the client - the
 * assessment payload returned to the Course Player omits correct_answer
 * entirely (enforced by a dedicated API resource/transformer, not shown
 * in this excerpt, rather than relying on the frontend to hide it).
 */
final class AssessmentService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly CertificateService $certificateService,
    ) {
    }

    /**
     * @param array<string, mixed> $submittedAnswers keyed by question_id
     */
    public function submitAttempt(Assessment $assessment, User $user, array $submittedAnswers): AssessmentAttempt
    {
        return DB::transaction(function () use ($assessment, $user, $submittedAnswers): AssessmentAttempt {
            $questions = $assessment->questions;
            $correctCount = 0;

            foreach ($questions as $question) {
                $submitted = $submittedAnswers[$question->id] ?? null;

                if ($this->answerMatches($question->correct_answer, $submitted)) {
                    $correctCount++;
                }
            }

            $score = $questions->count() > 0
                ? (int) round(($correctCount / $questions->count()) * 100)
                : 0;

            $passed = $score >= $assessment->passing_score;

            $attempt = AssessmentAttempt::create([
                'tenant_id' => TenantContext::current(),
                'assessment_id' => $assessment->id,
                'user_id' => $user->id,
                'score' => $score,
                'passed' => $passed,
                'answers' => $submittedAnswers,
                'submitted_at' => now(),
            ]);

            $this->auditLogger->log(
                tenantId: $attempt->tenant_id,
                actorUserId: $user->id,
                action: 'assessment.attempted',
                resourceType: 'assessment_attempt',
                resourceId: $attempt->id,
                afterState: ['score' => $score, 'passed' => $passed],
            );

            if ($passed) {
                $this->markCourseComplete($assessment, $user);
                $this->certificateService->issue($user, $assessment->course);
            }

            return $attempt;
        });
    }

    private function answerMatches(array $correctAnswer, mixed $submitted): bool
    {
        // Simple equality check for this excerpt's mcq/true_false cases;
        // scenario-based questions requiring partial-credit or rubric
        // scoring are a documented extension point for a future sprint,
        // not implemented here.
        return match (true) {
            isset($correctAnswer['option_id']) => $submitted === $correctAnswer['option_id'],
            isset($correctAnswer['value']) => $submitted === $correctAnswer['value'],
            default => false,
        };
    }

    private function markCourseComplete(Assessment $assessment, User $user): void
    {
        CourseAssignment::where('course_id', $assessment->course_id)
            ->where('user_id', $user->id)
            ->update(['status' => 'completed', 'completed_at' => now()]);
    }
}
