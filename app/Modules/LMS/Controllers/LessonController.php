<?php

declare(strict_types=1);

namespace App\Modules\LMS\Controllers;

use App\Modules\LMS\Models\Course;
use App\Modules\LMS\Models\Lesson;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Added in the Phase 11 code drop - the Course Builder screen
 * (resources/views/courses/builder.blade.php) needed lesson create/
 * reorder/delete endpoints that the original Epic E2 API surface
 * (Phase 8 Section 5) didn't include; that section specified course-level
 * and assessment-attempt endpoints only. Same "frontend work surfaces a
 * real backend gap, close it" pattern as the earlier myCourses and
 * PolicyController::index() fixes - this one is a full new controller
 * rather than a one-method addition, since lessons didn't have any
 * dedicated endpoint at all before this.
 */
final class LessonController
{
    public function store(Request $request, string $courseId): JsonResponse
    {
        abort_unless($request->user()->hasPermission('courses.manage'), 403);

        $course = Course::findOrFail($courseId);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content_type' => ['required', 'in:video,text,interactive'],
            'content_body' => ['required_if:content_type,text', 'nullable', 'string'],
            'content_url' => ['required_if:content_type,video,interactive', 'nullable', 'url'],
        ]);

        $nextOrder = $course->lessons()->max('sequence_order') + 1;

        $lesson = Lesson::create([
            'tenant_id' => TenantContext::current(),
            'course_id' => $course->id,
            'title' => $validated['title'],
            'content_type' => $validated['content_type'],
            'content_body' => $validated['content_body'] ?? null,
            'content_url' => $validated['content_url'] ?? null,
            'sequence_order' => $nextOrder,
        ]);

        return response()->json(['data' => $lesson, 'meta' => [], 'errors' => []], 201);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()->hasPermission('courses.manage'), 403);

        Lesson::findOrFail($id)->delete(); // soft delete, per Phase 5 schema

        return response()->json(['data' => null, 'meta' => [], 'errors' => []], 204);
    }

    /**
     * Persists a new lesson ordering, matching the Course Builder's
     * drag-drop reorder interaction. Accepts the full ordered list of
     * lesson IDs rather than a single moved-item delta - simplest correct
     * approach for a flat list of this size (Phase 6 Section 1's design
     * note on this screen), avoiding partial-update ordering bugs.
     */
    public function reorder(Request $request, string $courseId): JsonResponse
    {
        abort_unless($request->user()->hasPermission('courses.manage'), 403);

        $validated = $request->validate([
            'lesson_ids' => ['required', 'array'],
            'lesson_ids.*' => ['uuid', 'exists:lessons,id'],
        ]);

        foreach ($validated['lesson_ids'] as $index => $lessonId) {
            Lesson::where('id', $lessonId)
                ->where('course_id', $courseId) // belt-and-braces beyond the tenant scope: also confirm the lesson actually belongs to the course in the URL
                ->update(['sequence_order' => $index]);
        }

        return response()->json(['data' => null, 'meta' => [], 'errors' => []]);
    }
}
