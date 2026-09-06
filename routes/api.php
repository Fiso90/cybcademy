<?php

declare(strict_types=1);

use App\Modules\Employee\Controllers\EmployeeController;
use App\Modules\LMS\Controllers\CertificateController;
use Illuminate\Support\Facades\Route;

/*
| Public, unauthenticated endpoints - deliberately outside the
| auth:sanctum/tenant.context/mfa.verified group below. Rate-limited
| specifically (not just the general API throttle) per the Phase 8
| Section 7 risk item: this is the one intentionally exposed surface and
| must be monitored as such, not assumed low-risk because it's read-only.
*/
Route::middleware('throttle:20,1')
    ->get('/v1/certificates/verify/{certificateUid}', [CertificateController::class, 'verify']);

require __DIR__ . '/../app/Modules/PhishingSimulation/public_routes.php';

/*
|--------------------------------------------------------------------------
| API Routes - /api/v1/*
|--------------------------------------------------------------------------
|
| JWT-authenticated (Sanctum token guard), per Phase 4 Section 6 and
| Phase 8 Section 1. Every route below runs through, in order:
|   1. auth:sanctum          - resolves the authenticated principal
|   2. tenant.context        - App\Http\Middleware\SetTenantContext
|   3. mfa.verified          - App\Http\Middleware\EnsureMfaVerified
|
| This ordering matters: tenant context must be set before any
| tenant-scoped query can run, and MFA is checked only once we know who
| the user is and which tenant they belong to.
*/

Route::prefix('v1')
    ->middleware(['auth:sanctum', 'tenant.context', 'mfa.verified'])
    ->group(function (): void {
        Route::apiResource('employees', EmployeeController::class)
            ->except(['show']); // show() omitted from this excerpt for brevity
        Route::get('me/courses', [EmployeeController::class, 'myCourses']);

        require __DIR__ . '/../app/Modules/LMS/routes.php';
        require __DIR__ . '/../app/Modules/Policy/routes.php';
        require __DIR__ . '/../app/Modules/IncidentReporting/routes.php';
        require __DIR__ . '/../app/Modules/PhishingSimulation/routes.php';
        require __DIR__ . '/../app/Modules/Analytics/routes.php';
        require __DIR__ . '/../app/Modules/AuditCentre/routes.php';
        require __DIR__ . '/../app/Modules/AI/routes.php';
        require __DIR__ . '/../app/Modules/Billing/routes.php';
    });
