<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Models\Subscription;
use App\Support\TenantContext;

/**
 * Closes the gap flagged explicitly in the Epic E9 (AI Features) README:
 * "any authenticated user with the right role can call these endpoints
 * regardless of their tenant's subscription tier." This is the piece
 * that was missing.
 *
 * Feature-to-tier mapping matches Phase 3 Section 5 (Basic / Professional
 * adds Phishing Simulation + Analytics / Enterprise adds Audit Centre,
 * API, SSO, custom branding) plus Phase 1's explicit note that "Advanced
 * AI features (AI Executive Reports, AI Phishing Email Generator)" are
 * Enterprise-tier add-ons specifically, while the other three AI features
 * are available from Professional up.
 */
final class TierGateService
{
    private const TIER_RANK = ['basic' => 0, 'professional' => 1, 'enterprise' => 2];

    private const FEATURE_MINIMUM_TIER = [
        'phishing_simulation' => 'professional',
        'analytics' => 'professional',
        'audit_centre' => 'enterprise',
        'sso' => 'enterprise',
        'ai_quiz_generator' => 'professional',
        'ai_policy_summariser' => 'professional',
        'ai_risk_recommendations' => 'professional',
        'ai_executive_reports' => 'enterprise', // Phase 1's "Advanced AI features" note
        'ai_phishing_generator' => 'enterprise', // ditto
    ];

    public function tenantCanUse(string $featureKey): bool
    {
        $subscription = Subscription::where('status', '!=', 'cancelled')
            ->orderByDesc('created_at')
            ->first();

        if ($subscription === null || ! $subscription->isActive()) {
            return false;
        }

        $requiredTier = self::FEATURE_MINIMUM_TIER[$featureKey] ?? null;

        if ($requiredTier === null) {
            // Unmapped feature keys are treated as available on every
            // tier by default (fail open on "is this feature gated at
            // all", not on "does an inactive subscription get access") -
            // deliberately the opposite failure direction from an
            // expired/missing subscription, which fails closed above.
            // This means adding a new gated feature requires an explicit
            // entry in FEATURE_MINIMUM_TIER above, which is the intended
            // friction point for that decision.
            return true;
        }

        return self::TIER_RANK[$subscription->tier] >= self::TIER_RANK[$requiredTier];
    }

    public function assertTenantCanUse(string $featureKey): void
    {
        if (! $this->tenantCanUse($featureKey)) {
            abort(402, "This feature requires a subscription tier upgrade or an active subscription. "
                . "Feature: {$featureKey}");
        }
    }
}
