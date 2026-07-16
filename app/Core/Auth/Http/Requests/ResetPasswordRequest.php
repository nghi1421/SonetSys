<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Requests;

use App\Core\Auth\Application\DTOs\ResetPasswordData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', Password::min(8)],
        ];
    }

    public function toDto(): ResetPasswordData
    {
        return new ResetPasswordData(
            email: (string) $this->validated('email'),
            token: (string) $this->validated('token'),
            password: (string) $this->validated('password'),
        );
    }
}
