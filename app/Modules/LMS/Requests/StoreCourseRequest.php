<?php

declare(strict_types=1);

namespace App\Modules\LMS\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('courses.manage');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
