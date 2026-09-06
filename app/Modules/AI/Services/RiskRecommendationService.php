<?php

declare(strict_types=1);

namespace App\Modules\AI\Services;

use App\Modules\Analytics\Services\ExecutiveDashboardService;

/**
 * FR-9.5: AI Risk Recommendations, surfaced on the Compliance/Executive
 * dashboards per Phase 2. Same data-minimisation principle as
 * ExecutiveNarrativeService - department-level aggregates only, never
 * individual employee data, so a recommendation like "Finance needs
 * refresher phishing training" is possible, but "Jane in Finance keeps
 * failing simulations" never is.
 */
final class RiskRecommendationService
{
    public function __construct(
        private readonly AiGatewayService $ai,
        private readonly ExecutiveDashboardService $dashboard,
    ) {
    }

    /**
     * @return array<string> a short list of actionable recommendations
     */
    public function generateRecommendations(): array
    {
        $heatmap = $this->dashboard->departmentHeatmap();

        $systemPrompt = <<<'PROMPT'
            You recommend concrete, actionable next steps for a Compliance
            Officer based on department-level cyber risk scores (0-100, higher
            is better). Respond ONLY with a JSON array of 2-4 short strings,
            each a single actionable recommendation. Reference department
            names and scores directly; never speculate about individuals.
            PROMPT;

        $rawJson = $this->ai->generate(
            'risk_recommendations',
            $systemPrompt,
            'Department scores: ' . $heatmap->toJson(),
            maxTokens: 400,
        );

        $parsed = json_decode($rawJson, associative: true);

        return is_array($parsed) ? $parsed : [];
    }
}
