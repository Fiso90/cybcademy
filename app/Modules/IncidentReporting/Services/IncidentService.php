<?php

declare(strict_types=1);

namespace App\Modules\IncidentReporting\Services;

use App\Modules\Employee\Models\User;
use App\Modules\IncidentReporting\Models\Incident;
use App\Support\AuditLogger;
use App\Support\TenantContext;

/**
 * @see /mnt/user-data/outputs/CybCademy_Phase2_SRS.md Section 3.6 (FR-6.1, FR-6.2)
 */
final class IncidentService
{
    private const VALID_TRANSITIONS = [
        'open' => ['in_progress', 'closed'],
        'in_progress' => ['resolved', 'closed'],
        'resolved' => ['closed', 'in_progress'], // reopenable if resolution disputed
        'closed' => [],
    ];

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function submit(User $reporter, string $category, string $description): Incident
    {
        $incident = Incident::create([
            'tenant_id' => TenantContext::current(),
            'reported_by' => $reporter->id,
            'category' => $category,
            'description' => $description,
            'status' => 'open',
            'reported_at' => now(),
        ]);

        $this->auditLogger->log(
            tenantId: $incident->tenant_id,
            actorUserId: $reporter->id,
            action: 'incident.reported',
            resourceType: 'incident',
            resourceId: $incident->id,
            afterState: ['category' => $category],
        );

        return $incident;
    }

    /**
     * FR-6.2: Security Officer triage. Status transitions are validated
     * against an explicit state machine rather than accepting any string
     * the client sends, so an incident can't jump e.g. directly from
     * "open" to "closed" without passing through "in_progress" /
     * "resolved" - preserving a meaningful audit trail of how it was
     * actually handled.
     */
    public function updateStatus(string $incidentId, string $newStatus, ?string $assignToUserId, string $actorUserId): Incident
    {
        $incident = Incident::findOrFail($incidentId);
        $currentStatus = $incident->status;

        if (! in_array($newStatus, self::VALID_TRANSITIONS[$currentStatus] ?? [], true)) {
            throw new \DomainException("Cannot transition an incident from '{$currentStatus}' to '{$newStatus}'.");
        }

        $attributes = ['status' => $newStatus];

        if ($assignToUserId !== null) {
            $attributes['assigned_to'] = $assignToUserId;
        }

        if (in_array($newStatus, ['resolved', 'closed'], true)) {
            $attributes['resolved_at'] = now();
        }

        $incident->update($attributes);

        $this->auditLogger->log(
            tenantId: $incident->tenant_id,
            actorUserId: $actorUserId,
            action: 'incident.status_changed',
            resourceType: 'incident',
            resourceId: $incident->id,
            beforeState: ['status' => $currentStatus],
            afterState: ['status' => $newStatus],
        );

        return $incident->fresh();
    }
}
