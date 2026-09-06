<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Jobs;

use App\Modules\Analytics\Services\DepartmentRiskAggregator;
use App\Modules\Analytics\Services\HumanRiskScoreCalculator;
use App\Modules\Employee\Models\User;
use App\Modules\Organisation\Models\Department;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Recalculates every active employee's Human Risk Score for a tenant,
 * then rolls those up to department level. Scheduled to run on a regular
 * cadence (e.g. nightly) rather than recalculating synchronously on every
 * relevant event (course completion, phishing result, etc.) - the score
 * is deliberately a periodic snapshot, not a live-updating number on
 * every page load, matching the "calculated_at" time-series design in
 * Phase 5 Section 3.9 and keeping this off the interactive request path
 * per the p95 performance targets in Phase 2/3 NFRs.
 */
final class RecalculateHumanRiskScores implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $tenantId,
    ) {
    }

    public function handle(HumanRiskScoreCalculator $calculator, DepartmentRiskAggregator $aggregator): void
    {
        TenantContext::set($this->tenantId);

        User::where('status', 'active')->chunkById(200, function ($users) use ($calculator): void {
            foreach ($users as $user) {
                $calculator->calculateForUser($user);
            }
        });

        Department::chunkById(50, function ($departments) use ($aggregator): void {
            foreach ($departments as $department) {
                $aggregator->calculateForDepartment($department);
            }
        });
    }
}
