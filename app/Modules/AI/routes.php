<?php

declare(strict_types=1);

use App\Modules\AI\Controllers\AiController;
use Illuminate\Support\Facades\Route;

Route::prefix('ai')->group(function (): void {
    Route::post('/courses/{courseId}/generate-quiz', [AiController::class, 'generateQuiz']);
    Route::post('/policies/{policyId}/summarise', [AiController::class, 'summarisePolicy']);
    Route::post('/phishing-templates/generate', [AiController::class, 'generatePhishingTemplate']);
    Route::get('/executive-narrative', [AiController::class, 'executiveNarrative']);
    Route::get('/risk-recommendations', [AiController::class, 'riskRecommendations']);
});
