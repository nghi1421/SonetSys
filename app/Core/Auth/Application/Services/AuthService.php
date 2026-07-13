<?php

declare(strict_types=1);

namespace App\Core\Auth\Application\Services;

use App\Core\Auth\Application\Contracts\UserRepositoryInterface;
use App\Core\Auth\Application\DTOs\LoginData;
use App\Core\Auth\Application\DTOs\RegisterUserData;
use App\Core\Auth\Domain\Enums\RoleSlug;
use App\Core\Auth\Domain\Enums\UserStatus;
use App\Core\Auth\Domain\Models\Role;
use App\Core\Auth\Domain\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @return array{user: User, token: string}
     */
    public function register(RegisterUserData $data): array
    {
        return DB::transaction(function () use ($data): array {
            $role = Role::query()->firstOrCreate(
                ['tenant_id' => $data->tenantId, 'slug' => RoleSlug::Member->value],
                ['name' => 'Member'],
            );

            $user = $this->users->create([
                'tenant_id' => $data->tenantId,
                'role_id' => $role->id,
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
                'status' => UserStatus::Active,
            ]);

            return [
                'user' => $user,
                'token' => $user->createToken('api')->plainTextToken,
            ];
        });
    }

    /**
     * @return array{user: User, token: string}
     */
    public function login(LoginData $data): array
    {
        $user = $this->users->findByEmailForTenant($data->email, $data->tenantId);

        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if ($user->status !== UserStatus::Active) {
            throw ValidationException::withMessages([
                'email' => ['This account is not active.'],
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return [
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
