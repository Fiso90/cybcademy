<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

/**
 * The single, deliberate write path into the immutable `audit_logs` table.
 *
 * All security-relevant application actions MUST log through this class
 * rather than inserting into audit_logs directly, so there is exactly one
 * place that shapes what an audit entry looks like. Uses the query builder
 * (not an Eloquent model) since audit_logs intentionally has no
 * corresponding writable Eloquent model with update/delete capability.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase7_Security_Architecture.md Section 7
 */
final class AuditLogger
{
    public function log(
        ?string $tenantId,
        ?string $actorUserId,
        string $action,
        string $resourceType,
        ?string $resourceId = null,
        ?array $beforeState = null,
        ?array $afterState = null,
    ): void {
        if ($tenantId === null) {
            // System-level events with no resolvable tenant (e.g. a failed
            // login against an email that matched no account at all) are
            // still worth knowing about operationally, but do not belong in
            // a tenant-scoped, RLS-protected table. Route these to
            // standard application logging instead.
            logger()->warning("audit.unscoped.{$action}", compact('resourceType', 'resourceId'));

            return;
        }

        DB::table('audit_logs')->insert([
            'id' => Uuid::uuid4()->toString(),
            'tenant_id' => $tenantId,
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'before_state' => $beforeState !== null ? json_encode($beforeState) : null,
            'after_state' => $afterState !== null ? json_encode($afterState) : null,
            'ip_address' => request()?->ip(),
            'occurred_at' => now(),
            'created_at' => now(),
        ]);
    }
}
