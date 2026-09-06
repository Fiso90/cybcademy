<?php

declare(strict_types=1);

use App\Modules\AuditCentre\Controllers\AuditController;
use Illuminate\Support\Facades\Route;

Route::get('/audit-logs', [AuditController::class, 'index']);
Route::post('/audit/export', [AuditController::class, 'requestExport']);
Route::get('/audit/export/{jobId}', [AuditController::class, 'exportStatus']);
