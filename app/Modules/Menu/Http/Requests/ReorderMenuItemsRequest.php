<?php

declare(strict_types=1);

namespace App\Modules\Menu\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReorderMenuItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['required', 'integer', 'distinct', 'exists:menu_items,id'],
        ];
    }

    /**
     * @return array<int, int>
     */
    public function orderedIds(): array
    {
        return array_map(intval(...), $this->validated('order'));
    }
}
