<?php

declare(strict_types=1);

namespace App\Modules\AuditCentre\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Read-only access to audit_logs. Deliberately implemented against the
 * query builder rather than an Eloquent model, mirroring AuditLogger's
 * write path (App\Support\AuditLogger) - there is intentionally no
 * writable AuditLog Eloquent model anywhere in the codebase, so there is
 * no ->update() or ->delete() call that could even be attempted against
 * this table from application code, on top of the database-level
 * privilege revocation from the Epic E1 migration.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase2_SRS.md Section 3.8 (FR-8.1)
 */
final class AuditLogQueryService
{
    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $query = DB::table('audit_logs')
            ->leftJoin('users', 'users.id', '=', 'audit_logs.actor_user_id')
            ->select([
                'audit_logs.*',
                'users.name as actor_name',
            ])
            ->orderByDesc('audit_logs.occurred_at');

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['date_from'])) {
            $query->where('audit_logs.occurred_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->where('audit_logs.occurred_at', '<=', $filters['date_to']);
        }
        if (! empty($filters['resource_type'])) {
            $query->where('audit_logs.resource_type', $filters['resource_type']);
        }
        if (! empty($filters['action'])) {
            $query->where('audit_logs.action', 'like', $filters['action'] . '%');
        }
    }
}
