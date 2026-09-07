<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Http\Requests;

use App\Modules\Subscription\Domain\Enums\SubscriptionPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class SubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan' => ['required', new Enum(SubscriptionPlan::class)],
        ];
    }

    public function plan(): SubscriptionPlan
    {
        return SubscriptionPlan::from((string) $this->validated('plan'));
    }
}
