<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces Business Rule BR-2 (Phase 2 SRS): MFA is mandatory,
 * non-tenant-configurable, for any role carrying administrative or audit
 * privilege - System Administrator, Organisation Administrator,
 * Compliance Officer, Internal/IS Auditor, Security Officer.
 *
 * This check is deliberately NOT tenant-configurable, unlike general
 * password policy (Phase 2 FR-1.5, which IS per-tenant configurable) -
 * BR-2 is explicit that MFA enforcement for these roles is a platform
 * guarantee, not a customer setting a tenant admin could accidentally
 * weaken.
 *
 * @see /mnt/user-data/outputs/CybCademy_Phase2_SRS.md Section 6 (BR-2)
 */
final class EnsureMfaVerified
{
    /**
     * Roles for which MFA completion is required before any request
     * beyond the MFA challenge itself is permitted to proceed.
     */
    private const PRIVILEGED_ROLE_SLUGS = [
        'sys-admin',
        'org-admin',
        'compliance-officer',
        'internal-auditor',
        'is-auditor',
        'security-officer',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $requiresMfa = $user->roles()
            ->whereIn('slug', self::PRIVILEGED_ROLE_SLUGS)
            ->exists();

        if ($requiresMfa && ! $user->mfa_enabled) {
            abort(403, 'Multi-factor authentication is required for your role and has not '
                . 'yet been configured on this account. Please complete MFA setup to continue.');
        }

        if ($requiresMfa && ! session('mfa_verified_at')) {
            abort(403, 'Multi-factor authentication verification is required for this session.');
        }

        return $next($request);
    }
}
