<?php

declare(strict_types=1);

namespace App\Modules\LMS\Controllers;

use App\Modules\LMS\Models\Certificate;
use Illuminate\Http\JsonResponse;

/**
 * `verify()` is the one deliberately public, unauthenticated endpoint in
 * the API (Phase 8 Section 7 / Risks) - flagged there as needing explicit
 * rate limiting and monitoring precisely because it is the intentional
 * exception to "everything requires auth."
 */
final class CertificateController
{
    public function verify(string $certificateUid): JsonResponse
    {
        // withoutTenantScope() is correct and necessary here, not a bug:
        // an anonymous verifier (e.g. an employer checking a candidate's
        // claimed certificate) has no tenant context to authenticate
        // against, and certificate_uid is globally unique specifically to
        // support this cross-tenant lookup (see migration docblock,
        // 2026_01_02_000003).
        $certificate = Certificate::withoutTenantScope()
            ->where('certificate_uid', $certificateUid)
            ->with(['user:id,name', 'course:id,title'])
            ->first();

        if ($certificate === null) {
            return response()->json(['data' => null, 'meta' => [], 'errors' => [
                ['code' => 'not_found', 'message' => 'No certificate matches that ID.'],
            ]], 404);
        }

        // Deliberately minimal disclosure: confirm the certificate is
        // genuine and show the course/issue date, but do not expose the
        // holder's email, department, tenant name, or any other PII
        // beyond their name - this is a public endpoint.
        return response()->json(['data' => [
            'valid' => true,
            'holder_name' => $certificate->user->name,
            'course_title' => $certificate->course->title,
            'issued_at' => $certificate->issued_at,
        ], 'meta' => [], 'errors' => []]);
    }
}
