<?php

declare(strict_types=1);

namespace App\Console;

use App\Modules\Analytics\Jobs\RecalculateHumanRiskScores;
use App\Modules\Organisation\Models\Tenant;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Excerpt of app/Console/Kernel.php - closes two scheduling gaps that
 * have been explicitly flagged and deferred since Phase 10:
 *   - Epic E6's README: "The scheduled cadence for
 *     RecalculateHumanRiskScores... is not wired up in this excerpt -
 *     that's a one-line addition to app/Console/Kernel.php in Phase 13"
 *   - Epic E10's README: policy reminder scheduling "ready to be invoked
 *     on a schedule in Phase 13"
 *
 * Both are per-tenant jobs (Epic E1's tenant isolation extends to
 * scheduled work - a job dispatched without an explicit tenant context
 * would have none, and TenantContext::current() would return null), so
 * the schedule iterates all active tenants and dispatches one job per
 * tenant, rather than a single global job that would need to loop
 * tenants internally.
 */
class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Nightly Human Risk Score recalculation (Epic E6) - chosen cadence
        // matches that epic's own docblock ("periodic snapshot... e.g.
        // nightly"), not a real-time trigger, per its documented
        // performance rationale.
        $schedule->call(function (): void {
            Tenant::query()->pluck('id')->each(function (string $tenantId): void {
                RecalculateHumanRiskScores::dispatch($tenantId);
            });
        })->dailyAt('02:00')->name('recalculate-human-risk-scores')->withoutOverlapping();

        // Weekly outstanding policy acknowledgement reminders (Epic E10 /
        // FR-4.3). Iterates published policy titles per tenant, invoking
        // NotificationService::sendOutstandingPolicyReminders() (Epic
        // E10) for each - weekly, not daily, since a reminder cadence
        // more aggressive than that risks becoming the kind of nagging
        // that undermines the non-punitive tone established since Phase 1.
        $schedule->call(function (): void {
            Tenant::query()->pluck('id')->each(function (string $tenantId): void {
                \App\Support\TenantContext::set($tenantId);

                \App\Modules\Policy\Models\Policy::whereNotNull('published_at')
                    ->distinct()
                    ->pluck('title')
                    ->each(function (string $title): void {
                        app(\App\Modules\Notifications\Services\NotificationService::class)
                            ->sendOutstandingPolicyReminders($title);
                    });
            });
        })->weeklyOn(1, '09:00')->name('policy-acknowledgement-reminders')->withoutOverlapping();

        // Queue-cleanup / failed-job pruning - standard Laravel operational
        // hygiene, not tied to a specific epic.
        $schedule->command('queue:prune-failed --hours=720')->daily();
    }
}
