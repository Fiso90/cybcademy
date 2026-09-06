<?php

declare(strict_types=1);

namespace App\Modules\LMS\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SubmitAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Any authenticated employee may submit an attempt for an
        // assessment belonging to a course assigned to them - enforced in
        // the controller/service by checking course_assignments, not here,
        // since that check requires a database lookup beyond simple
        // request-shape validation.
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
        ];
    }
}
