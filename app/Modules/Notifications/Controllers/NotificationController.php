<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Controllers;

use App\Modules\Notifications\Models\Notification;
use App\Modules\Notifications\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationController
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json(['data' => $notifications->items(), 'meta' => [
            'page' => $notifications->currentPage(), 'per_page' => $notifications->perPage(), 'total' => $notifications->total(),
        ], 'errors' => []]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $this->notificationService->markRead($id, $request->user());

        return response()->json(['data' => null, 'meta' => [], 'errors' => []]);
    }
}
