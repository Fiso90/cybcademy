<?php

declare(strict_types=1);

namespace App\Modules\PhishingSimulation\Jobs;

use App\Modules\Employee\Models\User;
use App\Modules\PhishingSimulation\Models\PhishingCampaign;
use App\Modules\PhishingSimulation\Models\PhishingResult;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Sends a single simulated phishing email and records the initial 'sent'
 * event. Deliberately one job per recipient (not one job for the whole
 * campaign) so a single failed send retries independently and doesn't
 * block or duplicate the rest of the campaign.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase2_SRS.md Section 3.5 (FR-5.1)
 */
final class SendPhishingSimulationEmail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly string $campaignId,
        private readonly string $userId,
    ) {
    }

    public function handle(): void
    {
        $campaign = PhishingCampaign::withoutTenantScope()->findOrFail($this->campaignId);
        $user = User::withoutTenantScope()->findOrFail($this->userId);

        // Queue workers run outside an HTTP request, so there is no
        // middleware to set tenant context automatically - it must be set
        // explicitly here before any tenant-scoped write, matching the
        // pattern established in Phase 4 Section 9 for async work.
        TenantContext::set($campaign->tenant_id);

        $trackingToken = Str::uuid()->toString();

        // Actual mail sending (Mailable, transactional email provider
        // integration) is a Phase 13 tooling detail - the tracking token
        // embedded in the email's click-through link is the piece that
        // matters here, since it's what PhishingInteractionController
        // uses to attribute a click back to this specific
        // campaign+recipient pair without needing the link itself to
        // carry raw user/campaign IDs (which would make the simulation
        // trivially distinguishable from a real phishing link).

        PhishingResult::create([
            'tenant_id' => $campaign->tenant_id,
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'event_type' => 'sent',
            'event_at' => now(),
            'created_at' => now(),
        ]);

        cache()->put("phishing_tracking:{$trackingToken}", [
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
        ], now()->addDays(30));
    }
}
