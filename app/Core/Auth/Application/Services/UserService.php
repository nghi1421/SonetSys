<?php

declare(strict_types=1);

namespace App\Core\Auth\Application\Services;

use App\Core\Auth\Application\Contracts\UserRepositoryInterface;
use App\Core\Auth\Application\DTOs\UpdateUserData;
use App\Core\Auth\Domain\Models\Role;
use App\Core\Auth\Domain\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class UserService
{
    private const SEARCH_LIMIT = 10;

    public function __construct(
        private UserRepositoryInterface $users,
    ) {}

    public function list(int $page): LengthAwarePaginator
    {
        return $this->users->paginate($page, 25);
    }

    public function update(User $user, UpdateUserData $data): User
    {
        $role = Role::query()->where('slug', $data->role->value)->firstOrFail();

        return $this->users->update($user, [
            'role_id' => $role->id,
            'status' => $data->status,
        ]);
    }

    /**
     * @return Collection<int, User>
     */
    public function search(string $query): Collection
    {
        return $this->users->search($query, self::SEARCH_LIMIT);
    }
}
