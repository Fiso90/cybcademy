<?php

declare(strict_types=1);

namespace App\Modules\LMS\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AssignCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('courses.assign');
    }

    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['uuid', 'exists:users,id'],
            'due_date' => ['nullable', 'date', 'after:today'],
        ];
    }
}
