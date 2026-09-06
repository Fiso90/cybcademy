<?php

declare(strict_types=1);

namespace App\Modules\LMS\Listeners;

use App\Modules\Employee\Events\EmployeeOnboarded;
use App\Modules\LMS\Models\Course;
use App\Modules\LMS\Services\CourseService;
use Illuminate\Support\Collection;

/**
 * Listens for App\Modules\Employee\Events\EmployeeOnboarded (dispatched by
 * EmployeeService::create, Epic E1) and assigns whichever published
 * courses are configured as onboarding defaults for the tenant.
 *
 * This is the fulfilment of Phase 2 FR-2.4 ("employee lifecycle events...
 * triggering automatic training/policy assignment") and Phase 3 User
 * Flow 8.1 step 4 ("System auto-assigns onboarding course bundle").
 *
 * Registered in EventServiceProvider:
 *   EmployeeOnboarded::class => [AssignOnboardingCourses::class]
 */
final class AssignOnboardingCourses
{
    public function __construct(
        private readonly CourseService $courseService,
    ) {
    }

    public function handle(EmployeeOnboarded $event): void
    {
        $employee = $event->employee;

        // "Onboarding bundle" is modelled as courses flagged via a
        // tenant-configurable setting (e.g. a `courses.is_onboarding_default`
        // boolean, or a dedicated onboarding_course_bundles table) -
        // simplified here to a query against published courses tagged for
        // onboarding, with the actual tagging mechanism left as a
        // configuration detail for the tenant Settings module (Epic E10).
        $onboardingCourseIds = Course::where('status', 'published')
            ->where('is_onboarding_default', true)
            ->pluck('id');

        foreach ($onboardingCourseIds as $courseId) {
            $this->courseService->assign(
                courseId: $courseId,
                userIds: new Collection([$employee->id]),
                // null, not the employee's own ID - this is a system-
                // triggered assignment, not self-assignment, and the
                // audit trail (AuditLogger) should reflect that honestly
                // rather than attributing it to a human actor who didn't
                // take the action.
                assignedByUserId: null,
                dueDate: now()->addDays(14),
            );
        }
    }
}
