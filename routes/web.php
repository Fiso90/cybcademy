<?php

declare(strict_types=1);

use App\Modules\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/login', fn () => view('auth.login'))->name('auth.login');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login.attempt');
Route::get('/mfa/challenge', fn () => view('auth.mfa-challenge'))->name('auth.mfa.challenge');
Route::post('/mfa/challenge', [AuthController::class, 'mfaChallenge'])->name('auth.mfa.verify');
Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

Route::middleware(['auth', 'tenant.context', 'mfa.verified'])->group(function (): void {
    Route::get('/dashboard', function () {
        $user = auth()->user();

        $isDashboardRole = $user->hasRole('org-admin')
            || $user->hasRole('sys-admin')
            || $user->hasRole('compliance-officer')
            || $user->hasRole('security-officer')
            || $user->hasRole('manager');

        return $isDashboardRole
            ? view('dashboard.executive')
            : view('employee.home');
    })->name('dashboard');

    Route::get('/compliance', fn () => view('dashboard.compliance'))->name('dashboard.compliance');

    Route::get('/courses', fn () => view('courses.index'))->name('courses.index');
    Route::get('/courses/{course}/builder', fn (string $course) => view('courses.builder', ['courseId' => $course]))->name('courses.builder');
    Route::get('/courses/{course}/play', fn (string $course) => view('courses.player', ['courseId' => $course]))->name('courses.play');

    Route::get('/policies', fn () => view('policies.index'))->name('policies.index');
    Route::get('/phishing-campaigns', fn () => view('phishing.index'))->name('phishing.index');

    // Built in this final Phase 11 slice, replacing the earlier placeholders:
    Route::get('/audit', fn () => view('audit.index'))->name('audit.index');
    Route::get('/incidents/report', fn () => view('incidents.create'))->name('incidents.create');
});
