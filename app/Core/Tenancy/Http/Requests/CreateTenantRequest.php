<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Http\Requests;

use App\Core\Tenancy\Application\DTOs\CreateTenantData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            enabledModules: (array) $this->input('enabled_modules', []),
            settings: (array) $this->input('settings', []),
        );
    }
}
