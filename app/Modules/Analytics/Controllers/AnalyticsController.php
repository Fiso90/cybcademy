<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Controllers;

use App\Modules\Analytics\Services\ComplianceDashboardService;
use App\Modules\Analytics\Services\ExecutiveDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @see /mnt/user-data/outputs/CybCademy_Phase8_API_Design.md Section 8
 */
final class AnalyticsController
{
    public function __construct(
        private readonly ComplianceDashboardService $complianceDashboard,
        private readonly ExecutiveDashboardService $executiveDashboard,
    ) {
    }

    public function compliance(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()->hasRole('compliance-officer')
                || $request->user()->hasRole('org-admin')
                || $request->user()->hasRole('internal-auditor')
                || $request->user()->hasRole('is-auditor'),
            403
        );

        return response()->json(['data' => [
            'course_completion' => $this->complianceDashboard->courseCompletionSummary(),
            'policy_acknowledgement' => $this->complianceDashboard->policyAcknowledgementSummary(),
            'employees' => $this->complianceDashboard->employeeComplianceTable($request->query('department_id')),
        ], 'meta' => [], 'errors' => []]);
    }

    public function humanRiskScore(Request $request): JsonResponse
    {
        return response()->json(['data' => [
            'current_org_score' => $this->executiveDashboard->currentOrgScore(),
            'trend' => $this->executiveDashboard->orgWideTrend(
                (int) $request->query('months', 6)
            ),
            'department_heatmap' => $this->executiveDashboard->departmentHeatmap(),
        ], 'meta' => [], 'errors' => []]);
    }
}
