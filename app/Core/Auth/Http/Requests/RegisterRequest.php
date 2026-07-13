<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Requests;

use App\Core\Auth\Application\DTOs\RegisterUserData;
use App\Core\Tenancy\Application\TenantContext;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->id();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where(
                    fn ($query) => $tenantId === null
                        ? $query->whereNull('tenant_id')
                        : $query->where('tenant_id', $tenantId),
                ),
            ],
            'password' => ['required', 'string', Password::min(8)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (app(TenantContext::class)->current() === null) {
                $validator->errors()->add(
                    'tenant',
                    'No tenant could be resolved for this request. Register via your tenant subdomain or provide the X-Tenant-Slug header.',
                );
            }
        });
    }

    public function toDto(): RegisterUserData
    {
        return new RegisterUserData(
            name: (string) $this->validated('name'),
            email: (string) $this->validated('email'),
            password: (string) $this->validated('password'),
            tenantId: (int) app(TenantContext::class)->id(),
        );
    }
}
