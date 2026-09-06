<?php

declare(strict_types=1);

namespace App\Modules\AI\Services;

use App\Modules\Analytics\Services\ExecutiveDashboardService;

/**
 * FR-9.6: AI Executive Reports. Produces the plain-language narrative
 * referenced in Phase 3 User Flow / Phase 6 Section 4.1
 * ("Your organisation's risk trend improved 8% this quarter...").
 *
 * Deliberately takes only the already-computed, already-aggregated
 * trend/heatmap numbers from ExecutiveDashboardService as input - never
 * raw per-employee data. This narrative is read by executives and may be
 * pasted into board materials, so the AI should never see (and therefore
 * can never accidentally surface) anything at the individual-employee
 * level, keeping it consistent with BR-4's aggregation principle even
 * though this AI feature sits in Epic E9, not E4.
 */
final class ExecutiveNarrativeService
{
    public function __construct(
        private readonly AiGatewayService $ai,
        private readonly ExecutiveDashboardService $dashboard,
    ) {
    }

    public function generateNarrative(): string
    {
        $currentScore = $this->dashboard->currentOrgScore();
        $trend = $this->dashboard->orgWideTrend(months: 3);
        $heatmap = $this->dashboard->departmentHeatmap();

        $systemPrompt = 'You write brief, board-ready executive summaries of '
            . 'organisational cybersecurity risk trends. Two to three sentences. '
            . 'Plain language, no jargon. Note improvement or decline factually; '
            . 'do not editorialise or speculate about causes not evidenced in the data.';

        $userPrompt = 'Current organisation risk score (0-100, higher is better): '
            . ($currentScore ?? 'no data yet') . "\n"
            . 'Trailing 3-month trend (by month): ' . $trend->toJson() . "\n"
            . 'Department scores: ' . $heatmap->toJson();

        return $this->ai->generate('executive_reports', $systemPrompt, $userPrompt, maxTokens: 300);
    }
}
