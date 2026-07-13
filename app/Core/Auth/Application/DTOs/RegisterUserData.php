<?php

declare(strict_types=1);

namespace App\Core\Auth\Application\DTOs;

final readonly class RegisterUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public int $tenantId,
    ) {}
}
