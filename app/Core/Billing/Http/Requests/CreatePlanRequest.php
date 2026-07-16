<?php

declare(strict_types=1);

namespace App\Core\Billing\Http\Requests;

use App\Core\Billing\Application\DTOs\CreatePlanData;
use App\Core\Billing\Domain\Enums\PlanInterval;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class CreatePlanRequest extends FormRequest
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
                Rule::unique('plans', 'slug'),
            ],
            'price_cents' => ['required', 'integer', 'min:0'],
            'interval' => ['required', new Enum(PlanInterval::class)],
            'max_users' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'features' => ['sometimes', 'array'],
            'features.*' => ['string', 'max:255'],
        ];
    }

    public function toDto(): CreatePlanData
    {
        return new CreatePlanData(
            name: (string) $this->validated('name'),
            slug: (string) $this->validated('slug'),
            priceCents: (int) $this->validated('price_cents'),
            interval: PlanInterval::from((string) $this->validated('interval')),
            maxUsers: $this->validated('max_users'),
            features: (array) $this->input('features', []),
        );
    }
}
