<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Http\Requests;

use App\Core\Tenancy\Application\DTOs\CreateTenantData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class CreateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(['www', 'api']),
                Rule::unique('tenants', 'slug'),
            ],
            // No tenant-scoped uniqueness check needed for admin_email: the
            // tenant doesn't exist yet, so it can't already have any users.
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'string', 'email', 'max:255'],
            'admin_password' => ['required', 'string', Password::min(8)],
            'enabled_modules' => ['sometimes', 'array'],
            'enabled_modules.*' => ['string'],
            'settings' => ['sometimes', 'array'],
        ];
    }

    public function toDto(): CreateTenantData
    {
        return new CreateTenantData(
            name: (string) $this->validated('name'),
            slug: (string) $this->validated('slug'),
            adminName: (string) $this->validated('admin_name'),
            adminEmail: (string) $this->validated('admin_email'),
            adminPassword: (string) $this->validated('admin_password'),
            enabledModules: (array) $this->input('enabled_modules', []),
            settings: (array) $this->input('settings', []),
        );
    }
}
