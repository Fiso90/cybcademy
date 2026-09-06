<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Handles the web (session-based) login and MFA challenge flow.
 *
 * Per the Controller -> Service -> Repository -> Model layering (Phase 4
 * Section 4), this controller validates input and shapes the HTTP
 * response only - all business logic lives in AuthService.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase4_Architecture.md Section 6 (Authentication Flow)
 */
final class AuthController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $user = $this->authService->attemptLogin(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            ip: $request->ip(),
        );

        // Partially authenticate: the session knows who this is, but a
        // downstream request cannot proceed past EnsureMfaVerified
        // middleware until the MFA step (if required for this user's role)
        // is completed, per BR-2.
        Auth::login($user, remember: false);
        $request->session()->regenerate();

        if ($this->userRequiresMfa($user)) {
            return redirect()->route('auth.mfa.challenge');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function mfaChallenge(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'size:6']]);

        $user = $request->user();

        if (! $this->authService->verifyMfaCode($user, $request->string('code')->toString())) {
            throw ValidationException::withMessages([
                'code' => 'That verification code is incorrect or has expired.',
            ]);
        }

        // Regenerate session ID again on privilege escalation (Phase 7
        // Section 6) - completing MFA is itself a privilege change.
        $request->session()->regenerate();
        $request->session()->put('mfa_verified_at', now()->toIso8601String());

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login');
    }

    private function userRequiresMfa($user): bool
    {
        return $user->mfa_enabled;
    }
}
