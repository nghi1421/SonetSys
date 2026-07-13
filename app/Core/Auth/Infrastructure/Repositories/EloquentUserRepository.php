<?php

declare(strict_types=1);

namespace App\Core\Auth\Infrastructure\Repositories;

use App\Core\Auth\Application\Contracts\UserRepositoryInterface;
use App\Core\Auth\Domain\Models\User;

final class EloquentUserRepository implements UserRepositoryInterface
{
    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function findByEmailForTenant(string $email, ?int $tenantId): ?User
    {
        return User::query()
            ->where('email', $email)
            ->when(
                $tenantId === null,
                fn ($query) => $query->whereNull('tenant_id'),
                fn ($query) => $query->where('tenant_id', $tenantId),
            )
            ->first();
    }
}
