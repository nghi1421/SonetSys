<?php

declare(strict_types=1);

namespace App\Core\Auth\Domain\Enums;

enum RoleSlug: string
{
    case Admin = 'admin';
    case Moderator = 'moderator';
    case User = 'user';
}
