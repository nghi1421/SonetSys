<?php

declare(strict_types=1);

namespace App\Core\Auth\Application\Contracts;

use App\Core\Auth\Domain\Models\User;

interface UserRepositoryInterface
{
    public function create(array $attributes): User;

    public function findByEmail(string $email): ?User;
}
