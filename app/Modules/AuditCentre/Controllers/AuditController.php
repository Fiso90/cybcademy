<?php

declare(strict_types=1);

namespace App\Modules\AuditCentre\Controllers;

use App\Modules\AuditCentre\Requests\RequestAuditExportRequest;
use App\Modules\AuditCentre\Services\AuditExportService;
use App\Modules\AuditCentre\Services\AuditLogQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * @see /mnt/user-data/outputs/CybCademy_Phase8_API_Design.md Section 8
 */
final class AuditController
{
    public function __construct(
        private readonly AuditLogQueryService $queryService,
        private readonly AuditExportService $exportService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()->hasRole('compliance-officer')
                || $request->user()->hasRole('internal-auditor')
                || $request->user()->hasRole('is-auditor')
                || $request->user()->hasRole('org-admin')
                || $request->user()->hasRole('sys-admin'),
            403
        );

        $logs = $this->queryService->paginate($request->only(['date_from', 'date_to', 'resource_type', 'action']));

        return response()->json(['data' => $logs->items(), 'meta' => [
            'page' => $logs->currentPage(), 'per_page' => $logs->perPage(), 'total' => $logs->total(),
        ], 'errors' => []]);
    }

    public function requestExport(RequestAuditExportRequest $request): JsonResponse
    {
        $export = $this->exportService->requestExport($request->user(), $request->validated());

        return response()->json(['data' => [
            'export_id' => $export->id,
            'status' => $export->status,
        ], 'meta' => [], 'errors' => []], 202);
    }

    public function exportStatus(string $exportId): JsonResponse
    {
        $export = $this->exportService->status($exportId);

        $data = [
            'export_id' => $export->id,
            'status' => $export->status,
            'record_count' => $export->record_count,
            'completed_at' => $export->completed_at,
        ];

        if ($export->status === 'completed') {
            // Signed, time-limited URL per Phase 4 Section 10 - the export
            // file is never publicly addressable, and this link expires
            // rather than being a permanent, guessable-if-leaked URL.
            $data['download_url'] = Storage::disk('private')->temporaryUrl(
                $export->file_path,
                now()->addMinutes(15)
            );
        }

        return response()->json(['data' => $data, 'meta' => [], 'errors' => []]);
    }
}
