<?php

declare(strict_types=1);

namespace App\Modules\LMS\Services;

use App\Modules\Employee\Models\User;
use App\Modules\LMS\Models\Certificate;
use App\Modules\LMS\Models\Course;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Issues completion certificates (FR-3.4).
 *
 * `certificate_uid` is generated here rather than left to a database
 * default so it can use a format that is unambiguous to read aloud/type
 * (no ambiguous characters), matching the "publicly verifiable" use case
 * from Phase 8 Section 7 - someone may need to type this from a printed
 * certificate.
 */
final class CertificateService
{
    private const UID_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // excludes 0/O, 1/I/L

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function issue(User $user, Course $course): Certificate
    {
        // Idempotent: re-passing an assessment (e.g. a retake for a
        // refreshed certificate) does not issue duplicate certificates
        // for the same user/course pair.
        $existing = Certificate::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $uid = $this->generateUid();
        $filePath = "tenants/{$user->tenant_id}/certificates/{$uid}.pdf";

        // PDF rendering itself (Phase 13 tooling, e.g. a headless browser
        // or a PDF library) is out of scope for this excerpt - the
        // rendered file is written to $filePath by that step before this
        // record is created, or created here and populated by a queued
        // job; either is a valid implementation detail left open.

        $certificate = Certificate::create([
            'tenant_id' => TenantContext::current(),
            'user_id' => $user->id,
            'course_id' => $course->id,
            'certificate_uid' => $uid,
            'issued_at' => now(),
            'file_path' => $filePath,
        ]);

        $this->auditLogger->log(
            tenantId: $certificate->tenant_id,
            actorUserId: $user->id,
            action: 'certificate.issued',
            resourceType: 'certificate',
            resourceId: $certificate->id,
            afterState: ['certificate_uid' => $uid, 'course_id' => $course->id],
        );

        return $certificate;
    }

    private function generateUid(): string
    {
        do {
            $uid = 'CYB-' . $this->randomFromAlphabet(4) . '-' . $this->randomFromAlphabet(4);
        } while (Certificate::withoutTenantScope()->where('certificate_uid', $uid)->exists());

        return $uid;
    }

    /**
     * Str::random() draws from the full alphanumeric set, which includes
     * visually ambiguous characters (0/O, 1/I/L) unsuitable for a code a
     * person may need to transcribe from a printed certificate - so this
     * draws from the restricted UID_ALPHABET instead.
     */
    private function randomFromAlphabet(int $length): string
    {
        $alphabet = self::UID_ALPHABET;
        $max = strlen($alphabet) - 1;

        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $alphabet[random_int(0, $max)];
        }

        return $result;
    }
}
