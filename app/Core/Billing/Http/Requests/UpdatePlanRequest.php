<?php

declare(strict_types=1);

namespace App\Core\Billing\Http\Requests;

use App\Core\Billing\Application\DTOs\UpdatePlanData;
use App\Core\Billing\Domain\Enums\PlanInterval;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'interval' => ['required', new Enum(PlanInterval::class)],
            'max_users' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'features' => ['sometimes', 'array'],
            'features.*' => ['string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function toDto(): UpdatePlanData
    {
        return new UpdatePlanData(
            name: (string) $this->validated('name'),
            priceCents: (int) $this->validated('price_cents'),
            interval: PlanInterval::from((string) $this->validated('interval')),
            maxUsers: $this->validated('max_users'),
            features: (array) $this->input('features', []),
            isActive: (bool) $this->boolean('is_active', true),
        );
    }
}
