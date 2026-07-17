<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Requests;

use App\Core\Auth\Application\DTOs\UpdateUserData;
use App\Core\Auth\Domain\Enums\RoleSlug;
use App\Core\Auth\Domain\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', new Enum(RoleSlug::class)],
            'status' => ['required', new Enum(UserStatus::class)],
        ];
    }

    public function toDto(): UpdateUserData
    {
        return new UpdateUserData(
            role: RoleSlug::from((string) $this->validated('role')),
            status: UserStatus::from((string) $this->validated('status')),
        );
    }
}
