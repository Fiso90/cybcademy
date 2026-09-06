<?php

declare(strict_types=1);

namespace App\Modules\IncidentReporting\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Any authenticated employee can report an incident - this is
        // deliberately not permission-gated, per FR-6.1 and the Phase 6
        // design principle that reporting a suspected phishing email must
        // be fast and always accessible, not buried behind a permission
        // an ordinary employee might not have.
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'in:phishing,suspicious_activity,policy_violation,other'],
            'description' => ['required', 'string', 'max:5000'],
        ];
    }
}
