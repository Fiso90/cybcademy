<?php

declare(strict_types=1);

namespace App\Modules\AuditCentre\Services;

use App\Modules\AuditCentre\Jobs\GenerateAuditExportPackage;
use App\Modules\AuditCentre\Models\AuditExport;
use App\Modules\Employee\Models\User;
use App\Support\AuditLogger;
use App\Support\TenantContext;

/**
 * @see /mnt/user-data/outputs/CybCademy_Phase2_SRS.md Section 3.8 (FR-8.2 - 5-minute SLA)
 * @see /mnt/user-data/outputs/CybCademy_Phase3_PRD.md Section 8.4 (Audit Evidence Export user flow)
 */
final class AuditExportService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function requestExport(User $requester, array $filters): AuditExport
    {
        $export = AuditExport::create([
            'tenant_id' => TenantContext::current(),
            'requested_by' => $requester->id,
            'status' => 'pending',
            'filters' => $filters,
        ]);

        // The act of REQUESTING an export is itself a security-relevant
        // event worth logging (someone is pulling a bulk data extract) -
        // logged here, separately from whatever GenerateAuditExportPackage
        // logs on completion, so the request is on record even if
        // generation subsequently fails.
        $this->auditLogger->log(
            tenantId: $export->tenant_id,
            actorUserId: $requester->id,
            action: 'audit_export.requested',
            resourceType: 'audit_export',
            resourceId: $export->id,
            afterState: $filters,
        );

        GenerateAuditExportPackage::dispatch($export->id);

        return $export;
    }

    public function status(string $exportId): AuditExport
    {
        return AuditExport::findOrFail($exportId);
    }
}
