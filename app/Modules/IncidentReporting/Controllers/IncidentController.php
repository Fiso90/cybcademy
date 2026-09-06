<?php

declare(strict_types=1);

namespace App\Modules\IncidentReporting\Controllers;

use App\Modules\IncidentReporting\Models\Incident;
use App\Modules\IncidentReporting\Requests\StoreIncidentRequest;
use App\Modules\IncidentReporting\Requests\UpdateIncidentRequest;
use App\Modules\IncidentReporting\Services\IncidentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class IncidentController
{
    public function __construct(
        private readonly IncidentService $incidentService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Incident::query()->with(['reporter:id,name', 'assignee:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        $incidents = $query->orderByDesc('reported_at')->paginate(25);

        return response()->json(['data' => $incidents->items(), 'meta' => [
            'page' => $incidents->currentPage(), 'per_page' => $incidents->perPage(), 'total' => $incidents->total(),
        ], 'errors' => []]);
    }

    public function store(StoreIncidentRequest $request): JsonResponse
    {
        $incident = $this->incidentService->submit(
            $request->user(),
            $request->string('category')->toString(),
            $request->string('description')->toString(),
        );

        return response()->json(['data' => $incident, 'meta' => [], 'errors' => []], 201);
    }

    public function update(UpdateIncidentRequest $request, string $id): JsonResponse
    {
        try {
            $incident = $this->incidentService->updateStatus(
                $id,
                $request->string('status')->toString(),
                $request->input('assigned_to'),
                $request->user()->id,
            );
        } catch (\DomainException $e) {
            return response()->json(['data' => null, 'meta' => [], 'errors' => [
                ['code' => 'invalid_status_transition', 'message' => $e->getMessage()],
            ]], 422);
        }

        return response()->json(['data' => $incident, 'meta' => [], 'errors' => []]);
    }
}
