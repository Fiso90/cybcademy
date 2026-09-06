<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Services;

use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * @see /mnt/user-data/outputs/CybCademy_Phase2_SRS.md Section 3.7 (FR-7.2)
 */
final class ComplianceDashboardService
{
    public function courseCompletionSummary(): array
    {
        $rows = DB::table('course_assignments')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return [
            'assigned' => (int) ($rows['assigned'] ?? 0),
            'in_progress' => (int) ($rows['in_progress'] ?? 0),
            'completed' => (int) ($rows['completed'] ?? 0),
            'overdue' => (int) ($rows['overdue'] ?? 0),
        ];
    }

    public function policyAcknowledgementSummary(): array
    {
        // Outstanding = published policies without a matching
        // acknowledgement row for an active user - reuses the same query
        // shape as PolicyService::usersWithOutstandingAcknowledgement()
        // (Epic E3) at a tenant-wide summary level rather than per-policy.
        $totalActiveEmployees = DB::table('users')->where('status', 'active')->count();

        $publishedPolicies = DB::table('policies')->whereNotNull('published_at')->get(['id', 'title']);

        $summary = [];
        foreach ($publishedPolicies as $policy) {
            $acknowledgedCount = DB::table('policy_acknowledgements')
                ->where('policy_id', $policy->id)
                ->count();

            $summary[] = [
                'policy_id' => $policy->id,
                'title' => $policy->title,
                'acknowledged_count' => $acknowledgedCount,
                'outstanding_count' => max(0, $totalActiveEmployees - $acknowledgedCount),
            ];
        }

        return $summary;
    }

    /**
     * Exportable, filterable table view - the data behind Phase 6 Section
     * 4.2's "table-dense" Compliance Dashboard layout.
     */
    public function employeeComplianceTable(?string $departmentId = null): \Illuminate\Support\Collection
    {
        $query = DB::table('users')
            ->leftJoin('departments', 'departments.id', '=', 'users.department_id')
            ->where('users.status', 'active')
            ->select([
                'users.id',
                'users.name',
                'departments.name as department_name',
                'users.cached_human_risk_score',
            ]);

        if ($departmentId !== null) {
            $query->where('users.department_id', $departmentId);
        }

        return $query->get();
    }
}
