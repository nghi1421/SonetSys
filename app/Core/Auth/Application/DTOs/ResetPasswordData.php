<?php

declare(strict_types=1);

namespace App\Core\Auth\Application\DTOs;

final readonly class ResetPasswordData
{
    public function __construct(
        public string $email,
        public string $token,
        public string $password,
    ) {}
}
