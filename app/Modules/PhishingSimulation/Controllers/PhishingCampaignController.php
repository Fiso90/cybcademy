<?php

declare(strict_types=1);

namespace App\Modules\PhishingSimulation\Controllers;

use App\Modules\PhishingSimulation\Models\PhishingCampaign;
use App\Modules\PhishingSimulation\Models\PhishingTemplate;
use App\Modules\PhishingSimulation\Repositories\PhishingResultsRepository;
use App\Modules\PhishingSimulation\Requests\StorePhishingCampaignRequest;
use App\Modules\PhishingSimulation\Services\PhishingCampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @see /mnt/user-data/outputs/CybCademy_Phase8_API_Design.md Section 10
 */
final class PhishingCampaignController
{
    public function __construct(
        private readonly PhishingCampaignService $campaignService,
        private readonly PhishingResultsRepository $resultsRepository,
    ) {
    }

    /**
     * Added in the Phase 11 code drop - the Campaign Builder screen
     * needed a way to list selectable templates (both platform-provided
     * and this tenant's AI-generated ones), which the Epic E4 API
     * surface didn't include. Same gap-closing pattern as
     * LessonController and CourseController::show(). Relies on
     * PhishingTemplate's own global scope (Epic E4) to correctly include
     * both tenant_id IS NULL platform templates and this tenant's own -
     * no extra filtering needed here.
     */
    public function templates(): JsonResponse
    {
        return response()->json(['data' => PhishingTemplate::orderByDesc('created_at')->get(), 'meta' => [], 'errors' => []]);
    }

    public function index(): JsonResponse
    {
        $campaigns = PhishingCampaign::orderByDesc('created_at')->paginate(25);

        return response()->json(['data' => $campaigns->items(), 'meta' => [
            'page' => $campaigns->currentPage(), 'per_page' => $campaigns->perPage(), 'total' => $campaigns->total(),
        ], 'errors' => []]);
    }

    public function store(StorePhishingCampaignRequest $request): JsonResponse
    {
        $campaign = $this->campaignService->create($request->validated(), $request->user()->id);

        return response()->json(['data' => $campaign, 'meta' => [], 'errors' => []], 201);
    }

    public function launch(Request $request, string $id): JsonResponse
    {
        abort_unless(
            $request->user()->hasRole('security-officer') || $request->user()->hasRole('trainer'),
            403
        );

        try {
            $campaign = $this->campaignService->launch($id, $request->user()->id);
        } catch (\DomainException $e) {
            return response()->json(['data' => null, 'meta' => [], 'errors' => [
                ['code' => 'campaign_not_launchable', 'message' => $e->getMessage()],
            ]], 422);
        }

        return response()->json(['data' => $campaign, 'meta' => [], 'errors' => []]);
    }

    /**
     * Deliberately does NOT branch on role to decide which repository
     * method to call - that decision lives entirely inside
     * PhishingResultsRepository::resultsForRequester() (BR-4's single
     * enforcement point), so this controller cannot accidentally request
     * the wrong view for the wrong role. See the repository's docblock
     * for why that centralisation matters.
     */
    public function results(Request $request, string $id): JsonResponse
    {
        $campaign = PhishingCampaign::findOrFail($id);

        $results = $this->resultsRepository->resultsForRequester($campaign, $request->user());

        return response()->json(['data' => $results, 'meta' => [], 'errors' => []]);
    }
}
