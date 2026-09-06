<?php

declare(strict_types=1);

namespace App\Modules\LMS\Services;

use App\Modules\LMS\Models\Course;
use App\Modules\LMS\Repositories\CourseRepository;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * @see /mnt/user-data/outputs/CybCademy_Phase2_SRS.md Section 3.3 (FR-3.1, FR-3.5)
 */
final class CourseService
{
    public function __construct(
        private readonly CourseRepository $courses,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function list(array $filters): LengthAwarePaginator
    {
        return $this->courses->paginate($filters);
    }

    public function create(array $attributes, string $createdByUserId): Course
    {
        $attributes['tenant_id'] = TenantContext::current();
        $attributes['created_by'] = $createdByUserId;
        $attributes['status'] = 'draft';

        return $this->courses->create($attributes);
    }

    /**
     * Publishing is a distinct, explicit transition (FR-3.1) rather than a
     * generic "update status" call, so it can carry publish-specific
     * validation later (e.g. requiring at least one lesson) without that
     * logic getting buried inside a general-purpose update() method.
     */
    public function publish(string $courseId, string $actorUserId): Course
    {
        $course = $this->courses->findOrFail($courseId);

        if ($course->lessons->isEmpty()) {
            throw new \DomainException('A course must have at least one lesson before it can be published.');
        }

        $published = $this->courses->update($course, ['status' => 'published']);

        $this->auditLogger->log(
            tenantId: $published->tenant_id,
            actorUserId: $actorUserId,
            action: 'course.published',
            resourceType: 'course',
            resourceId: $published->id,
        );

        return $published;
    }

    /**
     * Implements FR-3.5 (assignment by role, department, or individual).
     * The caller resolves the target user ID collection (e.g. via the
     * Employee module's repository, filtered by department/role) - this
     * service deliberately stays agnostic to *how* the audience was
     * determined, keeping LMS decoupled from Organisation/Employee
     * internals per the module boundary in Phase 4 Section 3.
     */
    public function assign(string $courseId, Collection $userIds, ?string $assignedByUserId, ?\DateTimeInterface $dueDate = null): int
    {
        $course = $this->courses->findOrFail($courseId);

        if (! $course->isPublished()) {
            throw new \DomainException('Only published courses can be assigned.');
        }

        $assignedCount = $this->courses->assignToUsers($course, $userIds, $assignedByUserId, $dueDate);

        $this->auditLogger->log(
            tenantId: $course->tenant_id,
            actorUserId: $assignedByUserId,
            action: 'course.assigned',
            resourceType: 'course',
            resourceId: $course->id,
            afterState: ['assigned_count' => $assignedCount],
        );

        return $assignedCount;
    }
}
