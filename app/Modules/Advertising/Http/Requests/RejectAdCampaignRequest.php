<?php

declare(strict_types=1);

namespace App\Modules\Advertising\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RejectAdCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function reason(): string
    {
        return (string) $this->validated('reason');
    }
}
