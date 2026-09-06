<?php

declare(strict_types=1);

namespace App\Modules\Policy\Services;

use App\Modules\Employee\Models\User;
use App\Modules\Policy\Models\Policy;
use App\Modules\Policy\Models\PolicyAcknowledgement;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

/**
 * The single write path for FR-4.2 (acknowledgement tracking) / BR-3
 * (immutability). Deliberately a separate service from PolicyService,
 * since PolicyService manages the policy document lifecycle
 * (create/publish) while this handles the append-only acknowledgement
 * record - keeping the "no updates, ever" invariant scoped to as small a
 * surface as possible.
 */
final class PolicyAcknowledgementService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function acknowledge(Policy $policy, User $user, ?string $ipAddress): PolicyAcknowledgement
    {
        if (! $policy->isPublished()) {
            throw ValidationException::withMessages([
                'policy' => 'This policy has not been published and cannot be acknowledged yet.',
            ]);
        }

        try {
            $acknowledgement = PolicyAcknowledgement::create([
                'tenant_id' => TenantContext::current(),
                'policy_id' => $policy->id,
                'user_id' => $user->id,
                'acknowledged_at' => now(),
                'ip_address' => $ipAddress,
                'created_at' => now(),
            ]);
        } catch (QueryException $e) {
            // Unique constraint violation (tenant_id, policy_id, user_id)
            // means this user already acknowledged this exact policy
            // version - per BR-3, that is not an error to silently
            // swallow OR to overwrite; surface it plainly so the caller
            // (and audit trail) reflects reality rather than pretending a
            // second acknowledgement happened.
            throw ValidationException::withMessages([
                'policy' => 'You have already acknowledged this version of this policy.',
            ]);
        }

        $this->auditLogger->log(
            tenantId: $acknowledgement->tenant_id,
            actorUserId: $user->id,
            action: 'policy.acknowledged',
            resourceType: 'policy_acknowledgement',
            resourceId: $acknowledgement->id,
            afterState: ['policy_id' => $policy->id, 'policy_version' => $policy->version],
        );

        return $acknowledgement;
    }
}
