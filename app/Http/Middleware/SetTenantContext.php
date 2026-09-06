<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the authenticated user's tenant and locks it into
 * App\Support\TenantContext (and, via that class, the PostgreSQL session
 * variable backing Row-Level Security) before any downstream controller
 * or model code runs.
 *
 * This middleware MUST run before any middleware or controller logic that
 * touches a tenant-scoped model. Registered globally, immediately after
 * authentication resolution, in app/Http/Kernel.php.
 *
 * Per Phase 4 Section 7 / Phase 8 Section 1: no request path accepts a
 * client-supplied tenant_id parameter to override this. The tenant is
 * always derived from the authenticated principal (session user or JWT
 * claim), never from request input.
 */
final class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            // Unauthenticated requests (login, public certificate
            // verification endpoint) have no tenant context - RLS policies
            // correctly return zero rows for any tenant-scoped query
            // attempted without one, which is the fail-closed behaviour
            // intended by Phase 5 Section 6's migration default.
            TenantContext::clear();

            return $next($request);
        }

        TenantContext::set($user->tenant_id);

        return $next($request);
    }
}
