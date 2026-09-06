<?php

declare(strict_types=1);

use App\Modules\PhishingSimulation\Controllers\PhishingCampaignController;
use Illuminate\Support\Facades\Route;

/*
| Included from routes/api.php inside the authenticated group. The public
| click/report tracking endpoints live separately in
| app/Modules/PhishingSimulation/public_routes.php - see that file's
| docblock for why they must NOT be in this authenticated group.
*/

Route::prefix('phishing-campaigns')->group(function (): void {
    Route::get('/', [PhishingCampaignController::class, 'index']);
    Route::post('/', [PhishingCampaignController::class, 'store']);
    Route::post('/{id}/launch', [PhishingCampaignController::class, 'launch']);
    Route::get('/{id}/results', [PhishingCampaignController::class, 'results']);
});

Route::get('phishing-templates', [PhishingCampaignController::class, 'templates']);
