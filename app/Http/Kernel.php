<?php

declare(strict_types=1);

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

/**
 * Excerpt of app/Http/Kernel.php showing only the additions made for the
 * E1 Foundation epic. Merge these entries into the framework's default
 * Kernel rather than replacing it wholesale.
 */
class Kernel extends HttpKernel
{
    protected $middlewareGroups = [
        'web' => [
            // ...Laravel defaults (EncryptCookies, VerifyCsrfToken, etc.)...
        ],
        'api' => [
            // ...Laravel defaults (ThrottleRequests, SubstituteBindings)...
        ],
    ];

    /**
     * Route middleware aliases. `tenant.context` and `mfa.verified` are the
     * two additions this epic introduces; both are referenced by slug in
     * routes/web.php and routes/api.php.
     */
    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'tenant.context' => \App\Http\Middleware\SetTenantContext::class,
        'mfa.verified' => \App\Http\Middleware\EnsureMfaVerified::class,
    ];
}
