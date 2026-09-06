<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Services;

use App\Modules\Analytics\Models\HumanRiskScore;
use App\Modules\Organisation\Models\Department;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * Rolls up individual Human Risk Scores to department level, feeding the
 * Compliance/Executive Dashboards (Phase 3 Sections 8-9). Always reads
 * from the most recent per-user calculation, not a re-derivation from raw
 * training/assessment/phishing data - department scores are an aggregate
 * of individual scores, not an independently-weighted calculation, so the
 * two never tell contradictory stories about the same underlying facts.
 */
final class DepartmentRiskAggregator
{
    public function calculateForDepartment(Department $department): HumanRiskScore
    {
        // Latest score per user in this department, via a lateral-style
        // subquery pattern expressed through Eloquent - avoids averaging
        // stale historical scores alongside current ones.
        $latestScores = DB::table('human_risk_scores as hrs')
            ->join('users as u', 'u.id', '=', 'hrs.user_id')
            ->where('u.department_id', $department->id)
            ->whereNotNull('hrs.user_id')
            ->whereIn('hrs.id', function ($query) use ($department): void {
                $query->select(DB::raw('DISTINCT ON (user_id) id'))
                    ->from('human_risk_scores')
                    ->whereNotNull('user_id')
                    ->whereIn('user_id', function ($q) use ($department): void {
                        $q->select('id')->from('users')->where('department_id', $department->id);
                    })
                    ->orderBy('user_id')
                    ->orderByDesc('calculated_at');
            })
            ->pluck('hrs.score');

        $averageScore = $latestScores->isNotEmpty()
            ? round((float) $latestScores->avg(), 2)
            : 100.0;

        return HumanRiskScore::create([
            'tenant_id' => TenantContext::current(),
            'user_id' => null,
            'department_id' => $department->id,
            'score' => $averageScore,
            'calculated_at' => now(),
            'contributing_factors' => [
                'employee_count' => $latestScores->count(),
                'individual_scores_averaged' => true,
            ],
        ]);
    }
}
