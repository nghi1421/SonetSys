<?php

declare(strict_types=1);

namespace App\Modules\Group\Http\Requests;

use App\Modules\Group\Application\DTOs\CreateGroupData;
use App\Modules\Group\Domain\Enums\GroupVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class CreateGroupRequest extends FormRequest
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
            'visibility' => ['sometimes', new Enum(GroupVisibility::class)],
        ];
    }

    public function toDto(): CreateGroupData
    {
        $user = $this->user();

        return new CreateGroupData(
            name: (string) $this->validated('name'),
            description: $this->filled('description') ? (string) $this->validated('description') : null,
            visibility: $this->has('visibility')
                ? GroupVisibility::from((string) $this->validated('visibility'))
                : GroupVisibility::Public,
            tenantId: (int) $user->tenant_id,
            ownerId: (int) $user->id,
        );
    }
}
