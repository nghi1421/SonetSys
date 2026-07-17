<?php

declare(strict_types=1);

namespace App\Core\Auth\Infrastructure\Repositories;

use App\Core\Auth\Application\Contracts\UserRepositoryInterface;
use App\Core\Auth\Domain\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentUserRepository implements UserRepositoryInterface
{
    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function count(): int
    {
        return User::query()->count();
    }

    public function paginate(int $page, int $perPage): LengthAwarePaginator
    {
        return User::query()->with('role')->paginate($perPage, page: $page);
    }

    public function update(User $user, array $attributes): User
    {
        $user->fill($attributes)->save();

        return $user;
    }
}
