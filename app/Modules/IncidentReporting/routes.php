<?php

declare(strict_types=1);

use App\Modules\IncidentReporting\Controllers\IncidentController;
use Illuminate\Support\Facades\Route;

Route::prefix('incidents')->group(function (): void {
    Route::get('/', [IncidentController::class, 'index']);
    Route::post('/', [IncidentController::class, 'store']);
    Route::patch('/{id}', [IncidentController::class, 'update']);
});
