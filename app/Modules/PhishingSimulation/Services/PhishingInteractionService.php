<?php

declare(strict_types=1);

namespace App\Modules\PhishingSimulation\Services;

use App\Modules\PhishingSimulation\Models\PhishingResult;
use App\Support\TenantContext;

/**
 * Records an employee's interaction with a simulated phishing email
 * (click, credential submission, or report) and - critically - never
 * exposes the employee's identity or campaign context back to them at
 * the point of interaction beyond what's needed for the educational
 * redirect.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase2_SRS.md Section 3.5 (FR-5.3, FR-5.4)
 * @see /mnt/user-data/outputs/CybCademy_Phase6_UIUX.md Section 9 (non-punitive interface voice)
 */
final class PhishingInteractionService
{
    /**
     * Called when the tracking link (embedded by
     * SendPhishingSimulationEmail) is clicked. Looks up the tracking
     * token - NOT a raw campaign/user ID in the URL, so the link itself
     * gives no hint to the recipient (or anyone inspecting the email)
     * that it's part of a simulation before they click it.
     *
     * @return bool true if this was a valid simulation click (caller
     *               should redirect to the educational page); false if
     *               the token was invalid/expired (caller should show a
     *               generic "link expired" page, never an error that
     *               reveals this was a phishing simulation infrastructure
     *               endpoint).
     */
    public function recordClick(string $trackingToken): bool
    {
        $tracking = cache()->get("phishing_tracking:{$trackingToken}");

        if ($tracking === null) {
            return false;
        }

        TenantContext::set($this->resolveTenantId($tracking['campaign_id']));

        PhishingResult::create([
            'tenant_id' => TenantContext::current(),
            'campaign_id' => $tracking['campaign_id'],
            'user_id' => $tracking['user_id'],
            'event_type' => 'clicked',
            'event_at' => now(),
            'created_at' => now(),
        ]);

        return true;
    }

    /**
     * FR-5.4: one-click reporting of suspected phishing. Deliberately
     * accepts reports against BOTH real incoming email and simulation
     * tracking tokens through the same action, from the employee's point
     * of view - they should never have to guess whether something is
     * "real enough" to report. A report against a live simulation is
     * recorded as event_type 'reported' (a desired outcome, not a
     * failure) here; a report against a non-simulation email is routed to
     * the Incident Reporting module (Epic E5) instead, decided by whether
     * the tracking token resolves to an active campaign.
     */
    public function recordReport(string $trackingToken): bool
    {
        $tracking = cache()->get("phishing_tracking:{$trackingToken}");

        if ($tracking === null) {
            return false; // not a simulation - caller routes to Epic E5's IncidentService instead
        }

        TenantContext::set($this->resolveTenantId($tracking['campaign_id']));

        PhishingResult::create([
            'tenant_id' => TenantContext::current(),
            'campaign_id' => $tracking['campaign_id'],
            'user_id' => $tracking['user_id'],
            'event_type' => 'reported',
            'event_at' => now(),
            'created_at' => now(),
        ]);

        return true;
    }

    private function resolveTenantId(string $campaignId): string
    {
        return \App\Modules\PhishingSimulation\Models\PhishingCampaign::withoutTenantScope()
            ->findOrFail($campaignId)
            ->tenant_id;
    }
}
