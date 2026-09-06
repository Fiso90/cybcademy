<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Modules\Employee\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

/**
 * Encapsulates authentication business logic. Controllers delegate here
 * rather than talking to Auth::/Hash:: directly, per the Controller ->
 * Service -> Repository -> Model layering established in Phase 4 Section 4.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase7_Security_Architecture.md Section 3 (MFA), Section 10 (Rate Limiting)
 */
final class AuthService
{
    public function __construct(
        private readonly Google2FA $google2fa,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    /**
     * Attempts email/password authentication. Does NOT establish a fully
     * trusted session if the resolved user requires MFA - the caller
     * (AuthController) is responsible for redirecting to the MFA challenge
     * step before treating the session as fully authenticated.
     *
     * @throws ValidationException on invalid credentials or when the
     *                              per-account/per-IP rate limit (Phase 7
     *                              Section 10) has been exceeded.
     */
    public function attemptLogin(string $email, string $password, string $ip): User
    {
        $rateLimitKey = "login:{$ip}:" . strtolower($email);

        if (RateLimiter::tooManyAttempts($rateLimitKey, maxAttempts: 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        // Deliberately queries across all tenants at this stage - a user
        // does not know their own tenant_id before they've authenticated,
        // and email is only unique per-tenant (Phase 5 Section 5), so a
        // given email could legitimately resolve to more than one account
        // across different tenants. In that rare case we require the
        // tenant to be disambiguated via a tenant-slug login URL, handled
        // upstream of this method - out of scope for this excerpt.
        $user = User::withoutTenantScope()
            ->where('email', $email)
            ->where('status', 'active')
            ->first();

        if ($user === null || ! Hash::check($password, $user->password_hash)) {
            RateLimiter::hit($rateLimitKey, decaySeconds: 60);

            $this->auditLogger->log(
                tenantId: $user?->tenant_id,
                actorUserId: null,
                action: 'auth.login_failed',
                resourceType: 'user',
                resourceId: $user?->id,
            );

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($rateLimitKey);

        $user->forceFill(['last_login_at' => now()])->save();

        $this->auditLogger->log(
            tenantId: $user->tenant_id,
            actorUserId: $user->id,
            action: 'auth.login_succeeded',
            resourceType: 'user',
            resourceId: $user->id,
        );

        return $user;
    }

    public function verifyMfaCode(User $user, string $code): bool
    {
        if (! $user->mfa_enabled || $user->mfa_secret === null) {
            return false;
        }

        return $this->google2fa->verifyKey($user->mfa_secret, $code);
    }
}
