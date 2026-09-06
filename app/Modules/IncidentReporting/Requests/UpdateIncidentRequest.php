<?php

declare(strict_types=1);

namespace App\Modules\IncidentReporting\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // FR-6.2 restricts triage/status changes to Security Officer role,
        // matching Phase 8 Section 9's endpoint annotation.
        return $this->user()->hasRole('security-officer') || $this->user()->hasPermission('incidents.manage');
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
            'assigned_to' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }
}
