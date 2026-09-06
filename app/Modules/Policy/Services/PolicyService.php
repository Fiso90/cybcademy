<?php

declare(strict_types=1);

namespace App\Modules\Policy\Services;

use App\Modules\Policy\Models\Policy;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Collection;

/**
 * @see /mnt/user-data/outputs/CybCademy_Phase2_SRS.md Section 3.4 (FR-4.1, FR-4.3)
 */
final class PolicyService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function uploadNewVersion(string $title, string $filePath, string $actorUserId): Policy
    {
        $latestVersion = Policy::where('title', $title)->max('version') ?? 0;

        $policy = Policy::create([
            'tenant_id' => TenantContext::current(),
            'title' => $title,
            'version' => $latestVersion + 1,
            'file_path' => $filePath,
            'published_at' => null, // draft until explicitly published
        ]);

        $this->auditLogger->log(
            tenantId: $policy->tenant_id,
            actorUserId: $actorUserId,
            action: 'policy.version_uploaded',
            resourceType: 'policy',
            resourceId: $policy->id,
            afterState: ['title' => $title, 'version' => $policy->version],
        );

        return $policy;
    }

    public function publish(string $policyId, string $actorUserId): Policy
    {
        $policy = Policy::findOrFail($policyId);
        $policy->update(['published_at' => now()]);

        $this->auditLogger->log(
            tenantId: $policy->tenant_id,
            actorUserId: $actorUserId,
            action: 'policy.published',
            resourceType: 'policy',
            resourceId: $policy->id,
        );

        // Publishing a new version of a policy that superseded employees
        // already acknowledged does NOT retroactively mark them
        // acknowledged for the new version - the unique constraint on
        // (tenant_id, policy_id, user_id) means a fresh acknowledgement
        // row is required per version, correctly reopening the "who has
        // outstanding acknowledgements" question for everyone (FR-4.2/4.3).

        return $policy;
    }

    /**
     * FR-4.3: automated reminders for outstanding acknowledgements.
     * Returns the users who have NOT acknowledged the latest published
     * version of the given policy title - the actual reminder dispatch
     * (Notifications module, Epic E10) consumes this list; kept as a pure
     * query method here so it's independently testable.
     */
    public function usersWithOutstandingAcknowledgement(string $policyTitle): Collection
    {
        $latestPolicy = Policy::where('title', $policyTitle)
            ->whereNotNull('published_at')
            ->orderByDesc('version')
            ->first();

        if ($latestPolicy === null) {
            return collect();
        }

        return \App\Modules\Employee\Models\User::where('status', 'active')
            ->whereDoesntHave('policyAcknowledgements', function ($q) use ($latestPolicy): void {
                $q->where('policy_id', $latestPolicy->id);
            })
            ->get();
    }
}
