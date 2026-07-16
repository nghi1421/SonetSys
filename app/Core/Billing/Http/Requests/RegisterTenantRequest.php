<?php

declare(strict_types=1);

namespace App\Core\Billing\Http\Requests;

use App\Core\Billing\Application\DTOs\RegisterTenantData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class RegisterTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'company_slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(['www', 'api']),
                Rule::unique('tenants', 'slug'),
            ],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'string', 'email', 'max:255'],
            'admin_password' => ['required', 'string', Password::min(8)],
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')->where('is_active', true)],
        ];
    }

    public function toDto(): RegisterTenantData
    {
        return new RegisterTenantData(
            companyName: (string) $this->validated('company_name'),
            companySlug: (string) $this->validated('company_slug'),
            adminName: (string) $this->validated('admin_name'),
            adminEmail: (string) $this->validated('admin_email'),
            adminPassword: (string) $this->validated('admin_password'),
            planId: (int) $this->validated('plan_id'),
        );
    }
}
