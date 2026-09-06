<?php

declare(strict_types=1);

namespace App\Modules\Employee\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates employee creation input. Note the email uniqueness rule is
 * scoped to the current tenant only (Phase 5 Section 5 - composite unique
 * on tenant_id + email), matching the fact the same email may legitimately
 * belong to a different person/account in a different tenant.
 */
final class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('employees.manage');
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where('tenant_id', $tenantId),
            ],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
        ];
    }
}
