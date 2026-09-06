<?php

declare(strict_types=1);

use App\Modules\Policy\Controllers\PolicyController;
use Illuminate\Support\Facades\Route;

Route::prefix('policies')->group(function (): void {
    Route::get('/', [PolicyController::class, 'index']);
    Route::post('/', [PolicyController::class, 'store']);
    Route::post('/{id}/publish', [PolicyController::class, 'publish']);
    Route::get('/{id}/acknowledgements', [PolicyController::class, 'acknowledgements']);
    Route::post('/{id}/acknowledge', [PolicyController::class, 'acknowledge']);
});
