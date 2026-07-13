<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Requests;

use App\Core\Auth\Application\DTOs\LoginData;
use App\Core\Tenancy\Application\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function toDto(): LoginData
    {
        return new LoginData(
            email: (string) $this->validated('email'),
            password: (string) $this->validated('password'),
            tenantId: app(TenantContext::class)->id(),
        );
    }
}
