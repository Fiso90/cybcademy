<?php

declare(strict_types=1);

namespace App\Modules\Employee\Events;

use App\Modules\Employee\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when an employee is created/onboarded. Listened to by
 * App\Modules\LMS\Listeners\AssignOnboardingCourses and
 * App\Modules\Policy\Listeners\AssignOnboardingPolicies (Epics E2/E3),
 * fulfilling Phase 2 FR-2.4's automatic assignment requirement without
 * the Employee module needing to know anything about course or policy
 * internals.
 */
final class EmployeeOnboarded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly User $employee,
    ) {
    }
}
