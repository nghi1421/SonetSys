<?php

declare(strict_types=1);

namespace App\Modules\Advertising\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SubmitAdCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'budget' => ['required', 'integer', 'min:1'],
            'days' => ['required', 'integer', 'min:1', 'max:30'],
        ];
    }

    public function postId(): int
    {
        return (int) $this->validated('post_id');
    }

    public function budget(): int
    {
        return (int) $this->validated('budget');
    }

    public function days(): int
    {
        return (int) $this->validated('days');
    }
}
