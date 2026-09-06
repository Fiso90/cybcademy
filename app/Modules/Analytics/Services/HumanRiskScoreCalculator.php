<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Services;

use App\Modules\Employee\Models\User;
use App\Modules\LMS\Models\AssessmentAttempt;
use App\Modules\LMS\Models\CourseAssignment;
use App\Modules\PhishingSimulation\Models\PhishingResult;
use App\Modules\Analytics\Models\HumanRiskScore;
use App\Support\TenantContext;

/**
 * Calculates an individual employee's Human Risk Score - the platform's
 * signature metric (Phase 6 Section 1: "the Risk Ring").
 *
 * Weighting (documented here since it is the single most consequential,
 * customer-facing business decision in this class - if it changes, this
 * docblock and the corresponding Phase 3 KPI framing both need updating):
 *   - 40% training completion rate (on-time completion of assigned courses)
 *   - 30% assessment performance (average score across attempts)
 *   - 30% phishing simulation resilience (inverse of click-through rate,
 *     with 'reported' events weighted as a strong positive signal)
 *
 * A HIGHER score means LOWER risk (0-100 scale, 100 = lowest risk) -
 * chosen deliberately so the Risk Ring visually "fills up" as an
 * employee's risk improves, matching the positive, non-punitive framing
 * established in Phase 1/BR-4, rather than a "risk score" that goes up
 * as things get worse (which would visually read as an alarming, growing
 * red ring even for an improving employee).
 */
final class HumanRiskScoreCalculator
{
    private const TRAINING_WEIGHT = 0.40;
    private const ASSESSMENT_WEIGHT = 0.30;
    private const PHISHING_WEIGHT = 0.30;

    public function calculateForUser(User $user): HumanRiskScore
    {
        $trainingComponent = $this->trainingCompletionScore($user);
        $assessmentComponent = $this->assessmentPerformanceScore($user);
        $phishingComponent = $this->phishingResilienceScore($user);

        $overallScore = round(
            ($trainingComponent * self::TRAINING_WEIGHT)
            + ($assessmentComponent * self::ASSESSMENT_WEIGHT)
            + ($phishingComponent * self::PHISHING_WEIGHT),
            2
        );

        $score = HumanRiskScore::create([
            'tenant_id' => TenantContext::current(),
            'user_id' => $user->id,
            'department_id' => null,
            'score' => $overallScore,
            'calculated_at' => now(),
            'contributing_factors' => [
                'training_completion' => $trainingComponent,
                'assessment_performance' => $assessmentComponent,
                'phishing_resilience' => $phishingComponent,
                'weights' => [
                    'training' => self::TRAINING_WEIGHT,
                    'assessment' => self::ASSESSMENT_WEIGHT,
                    'phishing' => self::PHISHING_WEIGHT,
                ],
            ],
        ]);

        // Denormalised cache on the user record (Phase 5 Section 4) -
        // updated in the same operation that writes the source-of-truth
        // time-series row, so the two can never drift out of sync.
        $user->forceFill(['cached_human_risk_score' => $overallScore])->save();

        return $score;
    }

    private function trainingCompletionScore(User $user): float
    {
        $assignments = CourseAssignment::where('user_id', $user->id)->get();

        if ($assignments->isEmpty()) {
            // No assignments yet is treated as neutral (not penalised),
            // since a brand-new employee hasn't had the chance to
            // complete anything - scoring them as "high risk" on day one
            // would be misleading, not informative.
            return 100.0;
        }

        $onTimeCompletions = $assignments->filter(function (CourseAssignment $a): bool {
            return $a->status === 'completed'
                && ($a->due_date === null || $a->completed_at <= $a->due_date);
        })->count();

        return round(($onTimeCompletions / $assignments->count()) * 100, 2);
    }

    private function assessmentPerformanceScore(User $user): float
    {
        $attempts = AssessmentAttempt::where('user_id', $user->id)->get();

        if ($attempts->isEmpty()) {
            return 100.0; // same neutral-default reasoning as training
        }

        return round((float) $attempts->avg('score'), 2);
    }

    private function phishingResilienceScore(User $user): float
    {
        $results = PhishingResult::where('user_id', $user->id)
            ->whereIn('event_type', ['sent', 'clicked', 'reported'])
            ->get()
            ->groupBy('campaign_id');

        if ($results->isEmpty()) {
            return 100.0; // no simulations run yet for this employee
        }

        $campaignCount = $results->count();
        $goodOutcomes = 0;

        foreach ($results as $campaignEvents) {
            $types = $campaignEvents->pluck('event_type');

            // Reporting is the best outcome (full credit); not clicking
            // at all is a good outcome (full credit); clicking is a poor
            // outcome (zero credit for that campaign) - deliberately
            // binary per-campaign rather than partial credit, since
            // "clicked but then also reported" should not average out to
            // a middling score; the click already happened.
            if ($types->contains('reported') || ! $types->contains('clicked')) {
                $goodOutcomes++;
            }
        }

        return round(($goodOutcomes / $campaignCount) * 100, 2);
    }
}
