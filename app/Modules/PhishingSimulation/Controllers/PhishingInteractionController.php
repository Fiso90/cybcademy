<?php

declare(strict_types=1);

namespace App\Modules\PhishingSimulation\Controllers;

use App\Modules\PhishingSimulation\Services\PhishingInteractionService;
use Illuminate\Http\RedirectResponse;

/**
 * These two endpoints are, like certificate verification, deliberately
 * public and unauthenticated - a simulated phishing email's link cannot
 * require the recipient to already be logged in via a special channel,
 * or the simulation would be trivially distinguishable from a real
 * attack (defeating its purpose). Both are registered outside the
 * auth:sanctum/tenant.context/mfa.verified group in routes/api.php, same
 * as the certificate verification endpoint, and rate-limited
 * independently for the same reason given there (Phase 8 Section 7/10
 * Risks).
 */
final class PhishingInteractionController
{
    public function __construct(
        private readonly PhishingInteractionService $interactionService,
    ) {
    }

    /**
     * FR-5.3: redirects to just-in-time educational content regardless of
     * whether the token was valid, so no response difference could be
     * used to distinguish "this is a live simulation" from "this link is
     * dead" by an external observer probing the endpoint.
     */
    public function click(string $trackingToken): RedirectResponse
    {
        $this->interactionService->recordClick($trackingToken);

        return redirect()->route('phishing.education');
    }

    public function report(string $trackingToken): RedirectResponse
    {
        $wasSimulation = $this->interactionService->recordReport($trackingToken);

        return $wasSimulation
            ? redirect()->route('phishing.report-acknowledged')
            : redirect()->route('incidents.create'); // routes to Epic E5 for a genuine report
    }
}
