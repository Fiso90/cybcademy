<?php

declare(strict_types=1);

namespace App\Modules\PhishingSimulation\Services;

use App\Modules\Employee\Models\User;
use App\Modules\PhishingSimulation\Jobs\SendPhishingSimulationEmail;
use App\Modules\PhishingSimulation\Models\PhishingCampaign;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Collection;

/**
 * @see /mnt/user-data/outputs/CybCademy_Phase2_SRS.md Section 3.5 (FR-5.1, FR-5.2)
 */
final class PhishingCampaignService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function create(array $attributes, string $createdByUserId): PhishingCampaign
    {
        $attributes['tenant_id'] = TenantContext::current();
        $attributes['created_by'] = $createdByUserId;
        $attributes['status'] = 'draft';

        $campaign = PhishingCampaign::create($attributes);

        $this->auditLogger->log(
            tenantId: $campaign->tenant_id,
            actorUserId: $createdByUserId,
            action: 'phishing_campaign.created',
            resourceType: 'phishing_campaign',
            resourceId: $campaign->id,
        );

        return $campaign;
    }

    /**
     * Resolves target_scope (department_ids / role_slugs / user_ids) into
     * a concrete list of employees, then queues the actual send per
     * employee. Queued (not synchronous) per Phase 4 Section 9 and
     * Phase 7 Section 10 - a large campaign send must not block the
     * request/response cycle, and per-recipient send rate is capped by
     * the queue worker's configuration, not by how fast this loop can
     * dispatch jobs, guarding against the rate-limiting risk called out
     * in Phase 7 Section 10 (a compromised admin account spamming sends).
     */
    public function launch(string $campaignId, string $actorUserId): PhishingCampaign
    {
        $campaign = PhishingCampaign::findOrFail($campaignId);

        if ($campaign->status !== 'draft' && $campaign->status !== 'scheduled') {
            throw new \DomainException('Only draft or scheduled campaigns can be launched.');
        }

        $targets = $this->resolveTargets($campaign->target_scope);

        foreach ($targets as $user) {
            SendPhishingSimulationEmail::dispatch($campaign->id, $user->id)
                ->onQueue('phishing-sends'); // isolated queue, per Phase 4 Section 9
        }

        $campaign->update(['status' => 'sent']);

        $this->auditLogger->log(
            tenantId: $campaign->tenant_id,
            actorUserId: $actorUserId,
            action: 'phishing_campaign.launched',
            resourceType: 'phishing_campaign',
            resourceId: $campaign->id,
            afterState: ['target_count' => $targets->count()],
        );

        return $campaign->fresh();
    }

    private function resolveTargets(array $targetScope): Collection
    {
        $query = User::query()->where('status', 'active');

        if (! empty($targetScope['department_ids'])) {
            $query->whereIn('department_id', $targetScope['department_ids']);
        } elseif (! empty($targetScope['role_slugs'])) {
            $query->whereHas('roles', fn ($q) => $q->whereIn('slug', $targetScope['role_slugs']));
        } elseif (! empty($targetScope['user_ids'])) {
            $query->whereIn('id', $targetScope['user_ids']);
        } else {
            throw new \DomainException('target_scope must specify department_ids, role_slugs, or user_ids.');
        }

        return $query->get();
    }
}
