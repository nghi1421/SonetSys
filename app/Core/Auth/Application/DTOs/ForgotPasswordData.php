<?php

declare(strict_types=1);

namespace App\Core\Auth\Application\DTOs;

final readonly class ForgotPasswordData
{
    public function __construct(
        public string $email,
    ) {}
}
