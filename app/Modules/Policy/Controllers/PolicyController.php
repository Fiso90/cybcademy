<?php

declare(strict_types=1);

namespace App\Modules\Policy\Controllers;

use App\Modules\Policy\Models\Policy;
use App\Modules\Policy\Requests\UploadPolicyRequest;
use App\Modules\Policy\Services\PolicyAcknowledgementService;
use App\Modules\Policy\Services\PolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * @see /mnt/user-data/outputs/CybCademy_Phase8_API_Design.md Section 11
 */
final class PolicyController
{
    public function __construct(
        private readonly PolicyService $policyService,
        private readonly PolicyAcknowledgementService $acknowledgementService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        // Includes acknowledged_by_me per policy - added in the Phase 11
        // code drop because resources/views/policies/index.blade.php
        // needed it and the original Epic E3 endpoint didn't provide it
        // (it returned policy records only, with no per-caller
        // acknowledgement status). Same "frontend work surfaces a real
        // backend gap, fix it rather than fake it" pattern as
        // EmployeeController::myCourses() from the same drop.
        $userId = $request->user()->id;

        $policies = Policy::orderByDesc('version')->paginate(25);
        $acknowledgedPolicyIds = \App\Modules\Policy\Models\PolicyAcknowledgement::where('user_id', $userId)
            ->pluck('policy_id')
            ->flip();

        $items = collect($policies->items())->map(function (Policy $policy) use ($acknowledgedPolicyIds) {
            $policy->setAttribute('acknowledged_by_me', $acknowledgedPolicyIds->has($policy->id));

            return $policy;
        });

        return response()->json(['data' => $items, 'meta' => [
            'page' => $policies->currentPage(), 'per_page' => $policies->perPage(), 'total' => $policies->total(),
        ], 'errors' => []]);
    }

    public function store(UploadPolicyRequest $request): JsonResponse
    {
        // File type/size validated by UploadPolicyRequest per Phase 7
        // Section 14; stored outside the web root via the private disk,
        // never directly executable, served only via signed URLs.
        $path = $request->file('file')->store(
            'tenants/' . $request->user()->tenant_id . '/policies',
            'private'
        );

        $policy = $this->policyService->uploadNewVersion(
            title: $request->string('title')->toString(),
            filePath: $path,
            actorUserId: $request->user()->id,
        );

        return response()->json(['data' => $policy, 'meta' => [], 'errors' => []], 201);
    }

    public function publish(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()->hasPermission('policies.manage'), 403);

        $policy = $this->policyService->publish($id, $request->user()->id);

        return response()->json(['data' => $policy, 'meta' => [], 'errors' => []]);
    }

    public function acknowledgements(string $id): JsonResponse
    {
        $policy = Policy::findOrFail($id);

        return response()->json(['data' => $policy->acknowledgements()->with('user:id,name')->get(), 'meta' => [], 'errors' => []]);
    }

    public function acknowledge(Request $request, string $id): JsonResponse
    {
        $policy = Policy::findOrFail($id);

        try {
            $acknowledgement = $this->acknowledgementService->acknowledge(
                $policy,
                $request->user(),
                $request->ip(),
            );
        } catch (ValidationException $e) {
            return response()->json(['data' => null, 'meta' => [], 'errors' => [
                ['code' => 'acknowledgement_rejected', 'message' => $e->getMessage()],
            ]], 422);
        }

        return response()->json(['data' => $acknowledgement, 'meta' => [], 'errors' => []], 201);
    }
}
