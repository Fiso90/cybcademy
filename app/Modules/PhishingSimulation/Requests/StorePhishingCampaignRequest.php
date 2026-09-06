<?php

declare(strict_types=1);

namespace App\Modules\PhishingSimulation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StorePhishingCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        // FR-5.1 - restricted to Security Officer/Trainer, not general
        // Admin, since this is a sensitive, potentially disruptive action
        // (sends real-looking phishing emails to real employees).
        return $this->user()->hasRole('security-officer') || $this->user()->hasRole('trainer');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'template_id' => ['required', 'uuid', 'exists:phishing_templates,id'],
            'target_scope' => ['required', 'array'],
            'target_scope.department_ids' => ['sometimes', 'array'],
            'target_scope.department_ids.*' => ['uuid', 'exists:departments,id'],
            'target_scope.role_slugs' => ['sometimes', 'array'],
            'target_scope.user_ids' => ['sometimes', 'array'],
            'target_scope.user_ids.*' => ['uuid', 'exists:users,id'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
