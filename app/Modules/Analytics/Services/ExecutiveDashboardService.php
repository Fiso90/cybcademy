<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Services;

use App\Modules\Analytics\Models\HumanRiskScore;
use Illuminate\Support\Collection;

/**
 * @see /mnt/user-data/outputs/CybCademy_Phase2_SRS.md Section 3.7 (FR-7.3)
 * @see /mnt/user-data/outputs/CybCademy_Phase6_UIUX.md Section 4.1 (Executive Dashboard layout)
 */
final class ExecutiveDashboardService
{
    /**
     * Org-wide score trend, computed as the average of all department-
     * level scores at each calculated_at snapshot (department scores are
     * themselves already an average of individual scores - see
     * DepartmentRiskAggregator - so this is an average of averages,
     * deliberately, rather than re-averaging every individual score
     * directly, which would let a single very large department dominate
     * the org-wide number more than the "one department, one voice"
     * framing this platform's dashboards use elsewhere, e.g. the
     * department heatmap).
     */
    public function orgWideTrend(int $months = 6): Collection
    {
        return HumanRiskScore::whereNull('user_id')
            ->whereNotNull('department_id')
            ->where('calculated_at', '>=', now()->subMonths($months))
            ->orderBy('calculated_at')
            ->get()
            ->groupBy(fn (HumanRiskScore $s) => $s->calculated_at->format('Y-m'))
            ->map(fn (Collection $monthScores) => round((float) $monthScores->avg('score'), 2));
    }

    /**
     * Department heatmap data - one row per department, using each
     * department's most recent calculated score.
     */
    public function departmentHeatmap(): Collection
    {
        return HumanRiskScore::whereNotNull('department_id')
            ->with('department:id,name')
            ->orderByDesc('calculated_at')
            ->get()
            ->unique('department_id')
            ->map(fn (HumanRiskScore $s) => [
                'department_id' => $s->department_id,
                'department_name' => $s->department?->name,
                'score' => $s->score,
                'calculated_at' => $s->calculated_at,
            ])
            ->values();
    }

    public function currentOrgScore(): ?float
    {
        $trend = $this->orgWideTrend(months: 1);

        return $trend->isNotEmpty() ? (float) $trend->last() : null;
    }
}
