<?php

declare(strict_types=1);

namespace App\Modules\LMS\Controllers;

use App\Modules\LMS\Models\Assessment;
use App\Modules\LMS\Models\CourseAssignment;
use App\Modules\LMS\Requests\SubmitAssessmentRequest;
use App\Modules\LMS\Services\AssessmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AssessmentController
{
    public function __construct(
        private readonly AssessmentService $assessmentService,
    ) {
    }

    public function submitAttempt(SubmitAssessmentRequest $request, string $assessmentId): JsonResponse
    {
        $assessment = Assessment::findOrFail($assessmentId);
        $user = $request->user();

        // FR-3.5 / access-control: an employee can only attempt an
        // assessment for a course actually assigned to them, not any
        // published course in the tenant - enforced here rather than in
        // SubmitAssessmentRequest::authorize(), since it requires a
        // database lookup the Form Request layer intentionally keeps
        // lightweight (Phase 4 Section 4 - Form Requests validate shape,
        // Services enforce business rules).
        $isAssigned = CourseAssignment::where('course_id', $assessment->course_id)
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($isAssigned, 403, 'This assessment is not assigned to you.');

        $attempt = $this->assessmentService->submitAttempt(
            $assessment,
            $user,
            $request->validated('answers')
        );

        return response()->json(['data' => [
            'score' => $attempt->score,
            'passed' => $attempt->passed,
            'submitted_at' => $attempt->submitted_at,
        ], 'meta' => [], 'errors' => []], 201);
    }
}
