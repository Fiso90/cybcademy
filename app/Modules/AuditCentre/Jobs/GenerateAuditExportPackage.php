<?php

declare(strict_types=1);

namespace App\Modules\AuditCentre\Jobs;

use App\Modules\AuditCentre\Models\AuditExport;
use App\Modules\AuditCentre\Services\AuditLogQueryService;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Generates the actual export package, targeting the sub-5-minute SLA
 * from FR-8.2 for tenants up to 5,000 employees. Runs on its own
 * dedicated queue (not the general default queue, and not the
 * `phishing-sends` queue from Epic E4) so a backlog of phishing campaign
 * sends can never delay a compliance officer's audit evidence request -
 * these have very different urgency profiles and should not compete for
 * the same worker capacity.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase2_SRS.md Section 3.8 (FR-8.2)
 */
final class GenerateAuditExportPackage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;
    public int $timeout = 300; // 5 minutes - matches the SLA itself; a job running longer than the SLA has already failed it

    public function __construct(
        private readonly string $exportId,
    ) {
    }

    public function handle(AuditLogQueryService $queryService, AuditLogger $auditLogger): void
    {
        $export = AuditExport::withoutTenantScope()->findOrFail($this->exportId);
        TenantContext::set($export->tenant_id);

        $export->update(['status' => 'processing']);

        try {
            $records = DB::table('audit_logs')
                ->leftJoin('users', 'users.id', '=', 'audit_logs.actor_user_id')
                ->select(['audit_logs.*', 'users.name as actor_name'])
                ->when(! empty($export->filters['date_from']), fn ($q) => $q->where('audit_logs.occurred_at', '>=', $export->filters['date_from']))
                ->when(! empty($export->filters['date_to']), fn ($q) => $q->where('audit_logs.occurred_at', '<=', $export->filters['date_to']))
                ->orderBy('audit_logs.occurred_at')
                ->get();

            // CSV generation shown here as the concrete, minimal
            // implementation; PDF generation (also specified in FR-8.2)
            // follows the same query result through a templating/PDF
            // library step, a Phase 13 tooling detail not duplicated here.
            $csvPath = "tenants/{$export->tenant_id}/audit-exports/{$export->id}.csv";
            $csv = $this->toCsv($records);
            Storage::disk('private')->put($csvPath, $csv);

            $export->update([
                'status' => 'completed',
                'file_path' => $csvPath,
                'record_count' => $records->count(),
                'completed_at' => now(),
            ]);

            // Tamper-evident metadata per Phase 3 User Flow 8.4: the
            // export's own generation is logged, including who requested
            // it and how many records it contained, into the SAME
            // audit_logs table it just read from - so a future auditor
            // examining audit_logs can see that this export happened, by
            // whom, and how large it was, closing the loop on "who has
            // pulled evidence exports and when."
            $auditLogger->log(
                tenantId: $export->tenant_id,
                actorUserId: $export->requested_by,
                action: 'audit_export.completed',
                resourceType: 'audit_export',
                resourceId: $export->id,
                afterState: ['record_count' => $records->count()],
            );
        } catch (\Throwable $e) {
            $export->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);

            throw $e; // let the queue's retry/failed-job handling take over
        }
    }

    private function toCsv(\Illuminate\Support\Collection $records): string
    {
        if ($records->isEmpty()) {
            return "occurred_at,actor_name,action,resource_type,resource_id\n";
        }

        $lines = ['occurred_at,actor_name,action,resource_type,resource_id'];
        foreach ($records as $record) {
            $lines[] = implode(',', array_map(
                fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"',
                [$record->occurred_at, $record->actor_name, $record->action, $record->resource_type, $record->resource_id]
            ));
        }

        return implode("\n", $lines);
    }
}
