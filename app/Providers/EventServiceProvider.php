<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Employee\Events\EmployeeOnboarded;
use App\Modules\LMS\Listeners\AssignOnboardingCourses;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Excerpt of app/Providers/EventServiceProvider.php showing only the
 * registration added by Epic E2. Merge into the existing provider rather
 * than replacing it.
 *
 * This is the one place two modules' code paths are wired together
 * (Employee -> LMS) — everywhere else, module boundaries (Phase 4
 * Section 3) are respected by communicating through events like this one,
 * not direct cross-module service calls.
 */
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        EmployeeOnboarded::class => [
            AssignOnboardingCourses::class,
            // Phase 3's Policy Acknowledgement onboarding trigger
            // (Epic E3) registers its own listener here in that epic's
            // code drop, following the same pattern.
        ],
    ];
}
