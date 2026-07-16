<?php

declare(strict_types=1);

namespace App\Modules\Group\Http\Requests;

use App\Modules\Group\Application\DTOs\UpdateGroupData;
use App\Modules\Group\Domain\Enums\GroupVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class UpdateGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'visibility' => ['required', new Enum(GroupVisibility::class)],
        ];
    }

    public function toDto(): UpdateGroupData
    {
        return new UpdateGroupData(
            name: (string) $this->validated('name'),
            description: $this->filled('description') ? (string) $this->validated('description') : null,
            visibility: GroupVisibility::from((string) $this->validated('visibility')),
        );
    }
}
