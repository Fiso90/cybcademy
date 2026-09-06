<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

/**
 * Excerpt of app/Providers/AuthServiceProvider.php showing only the Gate
 * definitions added in the Phase 11 code drop, backing the @can checks in
 * resources/views/layouts/app.blade.php's role-aware sidebar navigation.
 * Merge into the existing provider rather than replacing it.
 *
 * Deliberately thin wrappers around User::hasPermission() /
 * User::hasRole() (Epic E1) rather than new authorization logic - Blade's
 * @can directive is just a more convenient call site for the same RBAC
 * checks every controller already performs, so the nav visibility and
 * the actual endpoint authorization can never silently disagree with
 * each other (both derive from the same underlying role/permission data).
 */
class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('courses.manage', fn ($user) => $user->hasPermission('courses.manage'));

        Gate::define('phishing_campaigns.view', fn ($user) =>
            $user->hasRole('security-officer') || $user->hasRole('trainer') || $user->hasRole('org-admin'));

        Gate::define('policies.view', fn ($user) =>
            $user->hasPermission('policies.manage') || $user->hasRole('compliance-officer'));

        Gate::define('audit.view', fn ($user) =>
            $user->hasRole('compliance-officer')
            || $user->hasRole('internal-auditor')
            || $user->hasRole('is-auditor')
            || $user->hasRole('org-admin')
            || $user->hasRole('sys-admin'));
    }
}
