<?php

declare(strict_types=1);

namespace App\Modules\LMS\Repositories;

use App\Modules\LMS\Models\Course;
use App\Modules\LMS\Models\CourseAssignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class CourseRepository
{
    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = Course::query()->with('creator');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function findOrFail(string $id): Course
    {
        return Course::with(['lessons', 'assessments'])->findOrFail($id);
    }

    public function create(array $attributes): Course
    {
        return Course::create($attributes);
    }

    public function update(Course $course, array $attributes): Course
    {
        $course->update($attributes);

        return $course->fresh();
    }

    /**
     * Bulk-creates course_assignments for a set of user IDs, skipping any
     * that already exist (Phase 5 Section 3.5 unique constraint on
     * tenant_id + course_id + user_id) so re-running an assignment
     * operation is idempotent rather than erroring.
     */
    public function assignToUsers(Course $course, Collection $userIds, ?string $assignedBy, ?\DateTimeInterface $dueDate): int
    {
        $existing = CourseAssignment::where('course_id', $course->id)
            ->whereIn('user_id', $userIds)
            ->pluck('user_id');

        $toAssign = $userIds->diff($existing);

        foreach ($toAssign as $userId) {
            CourseAssignment::create([
                'tenant_id' => $course->tenant_id,
                'course_id' => $course->id,
                'user_id' => $userId,
                'assigned_by' => $assignedBy,
                'due_date' => $dueDate,
                'status' => 'assigned',
            ]);
        }

        return $toAssign->count();
    }
}
