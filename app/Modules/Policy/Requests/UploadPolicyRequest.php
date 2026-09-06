<?php

declare(strict_types=1);

namespace App\Modules\Policy\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UploadPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('policies.manage');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf,docx', 'max:10240'], // 10MB, per Phase 7 Section 14
        ];
    }
}
