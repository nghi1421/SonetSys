<?php

declare(strict_types=1);

namespace App\Modules\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    public function body(): string
    {
        return (string) $this->validated('body');
    }
}
