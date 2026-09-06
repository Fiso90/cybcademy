<?php

declare(strict_types=1);

namespace App\Modules\AI\Controllers;

use App\Modules\AI\Services\ExecutiveNarrativeService;
use App\Modules\AI\Services\PhishingEmailGeneratorService;
use App\Modules\AI\Services\PolicySummariserService;
use App\Modules\AI\Services\QuizGeneratorService;
use App\Modules\AI\Services\RiskRecommendationService;
use App\Modules\Billing\Services\TierGateService;
use App\Modules\LMS\Models\Course;
use App\Modules\Policy\Models\Policy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AiController
{
    public function __construct(
        private readonly QuizGeneratorService $quizGenerator,
        private readonly PolicySummariserService $policySummariser,
        private readonly PhishingEmailGeneratorService $phishingGenerator,
        private readonly ExecutiveNarrativeService $executiveNarrative,
        private readonly RiskRecommendationService $riskRecommendations,
        private readonly TierGateService $tierGate,
    ) {
    }

    public function generateQuiz(Request $request, string $courseId): JsonResponse
    {
        abort_unless($request->user()->hasPermission('courses.manage'), 403);
        $this->tierGate->assertTenantCanUse('ai_quiz_generator');

        $course = Course::with('lessons')->findOrFail($courseId);

        try {
            $questions = $this->quizGenerator->generateForCourse(
                $course,
                (int) $request->input('question_count', 5)
            );
        } catch (\DomainException|\RuntimeException $e) {
            return response()->json(['data' => null, 'meta' => [], 'errors' => [
                ['code' => 'quiz_generation_failed', 'message' => $e->getMessage()],
            ]], 422);
        }

        return response()->json(['data' => [
            'questions' => $questions,
            'notice' => 'These questions are drafts and have not been reviewed. '
                . 'They must be explicitly attached to an assessment by a Trainer before use.',
        ], 'meta' => [], 'errors' => []], 201);
    }

    public function summarisePolicy(Request $request, string $policyId): JsonResponse
    {
        $this->tierGate->assertTenantCanUse('ai_policy_summariser');

        $policy = Policy::findOrFail($policyId);

        return response()->json(['data' => [
            'summary' => $this->policySummariser->summarise($policy),
        ], 'meta' => [], 'errors' => []]);
    }

    public function generatePhishingTemplate(Request $request): JsonResponse
    {
        // Same access restriction as StorePhishingCampaignRequest (Epic
        // E4) - deliberately duplicated here rather than shared, since a
        // Form Request's authorize() isn't reusable across a plain
        // Request-typed action without introducing a dedicated Form
        // Request class for this one field, which would be excessive for
        // a single string input.
        abort_unless(
            $request->user()->hasRole('security-officer') || $request->user()->hasRole('trainer'),
            403
        );
        $this->tierGate->assertTenantCanUse('ai_phishing_generator');

        $request->validate(['scenario' => ['required', 'string', 'max:500']]);

        $template = $this->phishingGenerator->generate($request->string('scenario')->toString());

        return response()->json(['data' => $template, 'meta' => [], 'errors' => []], 201);
    }

    public function executiveNarrative(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()->hasRole('org-admin') || $request->user()->hasRole('sys-admin'),
            403
        );
        $this->tierGate->assertTenantCanUse('ai_executive_reports');

        return response()->json(['data' => [
            'narrative' => $this->executiveNarrative->generateNarrative(),
        ], 'meta' => [], 'errors' => []]);
    }

    public function riskRecommendations(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()->hasRole('compliance-officer') || $request->user()->hasRole('org-admin'),
            403
        );
        $this->tierGate->assertTenantCanUse('ai_risk_recommendations');

        return response()->json(['data' => [
            'recommendations' => $this->riskRecommendations->generateRecommendations(),
        ], 'meta' => [], 'errors' => []]);
    }
}
