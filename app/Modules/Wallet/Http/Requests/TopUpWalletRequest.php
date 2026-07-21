<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TopUpWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'note' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function amount(): int
    {
        return (int) $this->validated('amount');
    }

    public function note(): ?string
    {
        $note = $this->validated('note');

        return $note !== null ? (string) $note : null;
    }
}
