<?php

declare(strict_types=1);

namespace App\Modules\Menu\Http\Requests;

use App\Modules\Menu\Application\DTOs\UpdateMenuItemData;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateMenuItemRequest extends FormRequest
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

    public function toDto(): UpdateMenuItemData
    {
        return new UpdateMenuItemData(
            label: (string) $this->validated('label'),
            staticPageId: $this->input('static_page_id') !== null ? (int) $this->input('static_page_id') : null,
        );
    }
}
