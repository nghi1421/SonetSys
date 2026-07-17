<?php

declare(strict_types=1);

namespace App\Core\Auth\Application\DTOs;

use App\Core\Auth\Domain\Enums\RoleSlug;
use App\Core\Auth\Domain\Enums\UserStatus;

final readonly class UpdateUserData
{
    public function __construct(
        public RoleSlug $role,
        public UserStatus $status,
    ) {}
}
