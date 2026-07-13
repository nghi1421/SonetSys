<?php

declare(strict_types=1);

namespace App\Core\Auth\Application\DTOs;

final readonly class LoginData
{
    public function __construct(
        public string $email,
        public string $password,
        public ?int $tenantId,
    ) {}
}
