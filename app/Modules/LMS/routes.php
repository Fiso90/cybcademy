<?php

declare(strict_types=1);

use App\Modules\LMS\Controllers\AssessmentController;
use App\Modules\LMS\Controllers\CourseController;
use App\Modules\LMS\Controllers\LessonController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| LMS Module Routes
|--------------------------------------------------------------------------
|
| Included from routes/api.php inside the same
| ['auth:sanctum', 'tenant.context', 'mfa.verified'] middleware group
| established in Epic E1, per Phase 8 Section 5.
*/

Route::prefix('courses')->group(function (): void {
    Route::get('/', [CourseController::class, 'index']);
    Route::get('/{id}', [CourseController::class, 'show']);
    Route::post('/', [CourseController::class, 'store']);
    Route::post('/{id}/publish', [CourseController::class, 'publish']);
    Route::post('/{id}/assign', [CourseController::class, 'assign']);

    // Added in the Phase 11 code drop - see LessonController's docblock.
    Route::post('/{courseId}/lessons', [LessonController::class, 'store']);
    Route::post('/{courseId}/lessons/reorder', [LessonController::class, 'reorder']);
});

Route::delete('lessons/{id}', [LessonController::class, 'destroy']);

Route::prefix('assessments')->group(function (): void {
    Route::post('/{assessmentId}/attempts', [AssessmentController::class, 'submitAttempt']);
});
