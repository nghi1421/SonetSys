<?php

declare(strict_types=1);

namespace App\Modules\Menu\Http\Requests;

use App\Modules\Menu\Application\DTOs\CreateMenuItemData;
use Illuminate\Foundation\Http\FormRequest;

final class CreateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:64'],
            'static_page_id' => ['sometimes', 'nullable', 'integer', 'exists:static_pages,id'],
        ];
    }

    public function toDto(): CreateMenuItemData
    {
        return new CreateMenuItemData(
            label: (string) $this->validated('label'),
            staticPageId: $this->input('static_page_id') !== null ? (int) $this->input('static_page_id') : null,
        );
    }
}
