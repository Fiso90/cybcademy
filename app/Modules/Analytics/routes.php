<?php

declare(strict_types=1);

use App\Modules\Analytics\Controllers\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->group(function (): void {
    Route::get('/compliance', [AnalyticsController::class, 'compliance']);
    Route::get('/human-risk-score', [AnalyticsController::class, 'humanRiskScore']);
});
