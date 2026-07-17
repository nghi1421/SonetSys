<?php

declare(strict_types=1);

namespace App\Core\Auth\Application\Contracts;

use App\Core\Auth\Domain\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function create(array $attributes): User;

    public function findByEmail(string $email): ?User;

    public function count(): int;

    public function paginate(int $page, int $perPage): LengthAwarePaginator;

    public function update(User $user, array $attributes): User;
}
