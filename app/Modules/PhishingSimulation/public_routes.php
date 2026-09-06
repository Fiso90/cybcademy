<?php

declare(strict_types=1);

use App\Modules\PhishingSimulation\Controllers\PhishingInteractionController;
use Illuminate\Support\Facades\Route;

/*
| Public, unauthenticated tracking endpoints - see
| PhishingInteractionController's docblock for why. Included directly
| from routes/api.php OUTSIDE the auth:sanctum/tenant.context/mfa.verified
| group, alongside the certificate verification route.
|
| Rate-limited per Phase 7 Section 10 - a burst of hits against these
| endpoints from a single source is itself a signal worth monitoring
| (e.g. someone scripting a check to see which tokens are "live"), not
| just a resource-protection measure.
*/

Route::middleware('throttle:60,1')->group(function (): void {
    Route::get('/v1/phishing/click/{trackingToken}', [PhishingInteractionController::class, 'click'])
        ->name('phishing.click');
    Route::get('/v1/phishing/report/{trackingToken}', [PhishingInteractionController::class, 'report'])
        ->name('phishing.report');
});
