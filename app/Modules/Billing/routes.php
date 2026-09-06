<?php

declare(strict_types=1);

use App\Modules\KnowledgeBase\Controllers\KnowledgeBaseController;
use App\Modules\Notifications\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('notifications')->group(function (): void {
    Route::get('/', [NotificationController::class, 'index']);
    Route::post('/{id}/read', [NotificationController::class, 'markRead']);
});

Route::prefix('knowledge-base')->group(function (): void {
    Route::get('/', [KnowledgeBaseController::class, 'index']);
    Route::post('/', [KnowledgeBaseController::class, 'store']);
    Route::post('/{id}/publish', [KnowledgeBaseController::class, 'publish']);
});
