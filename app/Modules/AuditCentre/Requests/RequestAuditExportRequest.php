<?php

declare(strict_types=1);

namespace App\Modules\AuditCentre\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RequestAuditExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        // FR-8.2 restricts export generation to Auditor/Compliance roles -
        // matches the role list used for the read-only /audit-logs
        // endpoint (AuditController::index) for consistency, since export
        // is just a bulk, downloadable form of the same data.
        return $this->user()->hasRole('compliance-officer')
            || $this->user()->hasRole('internal-auditor')
            || $this->user()->hasRole('is-auditor')
            || $this->user()->hasRole('org-admin');
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'resource_types' => ['nullable', 'array'],
        ];
    }
}
