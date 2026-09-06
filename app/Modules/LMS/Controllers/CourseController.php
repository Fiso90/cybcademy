<?php

declare(strict_types=1);

namespace App\Modules\LMS\Controllers;

use App\Modules\LMS\Models\Course;
use App\Modules\LMS\Requests\AssignCourseRequest;
use App\Modules\LMS\Requests\StoreCourseRequest;
use App\Modules\LMS\Services\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * @see /mnt/user-data/outputs/CybCademy_Phase8_API_Design.md Section 5
 */
final class CourseController
{
    public function __construct(
        private readonly CourseService $courseService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $courses = $this->courseService->list($request->only(['status']));

        return response()->json(['data' => $courses->items(), 'meta' => [
            'page' => $courses->currentPage(), 'per_page' => $courses->perPage(), 'total' => $courses->total(),
        ], 'errors' => []]);
    }

    /**
     * Added in the Phase 11 code drop - the Course Builder screen needed
     * a single-course-with-lessons fetch, which the Epic E2 API surface
     * didn't include (Phase 8 Section 5 specified list/create/publish/
     * assign, not a show() endpoint). Same gap-closing pattern as
     * LessonController.
     */
    public function show(string $id): JsonResponse
    {
        $course = Course::with(['lessons', 'assessments.questions'])->findOrFail($id);

        // Strip correct_answer from every question before this response
        // leaves the server. Caught during the Phase 11 Course Player
        // build: Assessment::questions() (Epic E2) eager-loads the full
        // QuestionBankItem, including correct_answer, and nothing in the
        // original show() implementation removed it - meaning a
        // technically savvy employee could read correct answers straight
        // out of the browser's network tab, directly contradicting
        // AssessmentService's own docblock ("Grading is deliberately
        // server-side... never sent to the client"). This is now enforced
        // here, at the one place a course (and its assessments) leaves
        // the server, rather than left as a client-side "don't render it"
        // convention that inspecting the payload would have defeated.
        $course->assessments->each(function ($assessment): void {
            $assessment->questions->each(function ($question): void {
                $question->makeHidden('correct_answer');
            });
        });

        return response()->json(['data' => $course, 'meta' => [], 'errors' => []]);
    }

    public function store(StoreCourseRequest $request): JsonResponse
    {
        $course = $this->courseService->create($request->validated(), $request->user()->id);

        return response()->json(['data' => $course, 'meta' => [], 'errors' => []], 201);
    }

    public function publish(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()->hasPermission('courses.manage'), 403);

        try {
            $course = $this->courseService->publish($id, $request->user()->id);
        } catch (\DomainException $e) {
            return response()->json(['data' => null, 'meta' => [], 'errors' => [
                ['code' => 'course_not_publishable', 'message' => $e->getMessage()],
            ]], 422);
        }

        return response()->json(['data' => $course, 'meta' => [], 'errors' => []]);
    }

    public function assign(AssignCourseRequest $request, string $id): JsonResponse
    {
        try {
            $assignedCount = $this->courseService->assign(
                courseId: $id,
                userIds: new Collection($request->validated('user_ids')),
                assignedByUserId: $request->user()->id,
                dueDate: $request->filled('due_date') ? new \DateTimeImmutable($request->string('due_date')) : null,
            );
        } catch (\DomainException $e) {
            return response()->json(['data' => null, 'meta' => [], 'errors' => [
                ['code' => 'course_not_assignable', 'message' => $e->getMessage()],
            ]], 422);
        }

        return response()->json(['data' => ['assigned_count' => $assignedCount], 'meta' => [], 'errors' => []]);
    }
}
