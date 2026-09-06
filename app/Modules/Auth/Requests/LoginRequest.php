<?php

declare(strict_types=1);

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the initial email/password login step. MFA code (if required)
 * is validated separately by MfaChallengeRequest, kept as a distinct step
 * so a failed MFA attempt never reveals whether the password itself was
 * correct.
 */
final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // unauthenticated endpoint - authorization is the point of the request
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }
}
