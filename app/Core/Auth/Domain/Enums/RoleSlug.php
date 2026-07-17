<?php

declare(strict_types=1);

namespace App\Core\Auth\Domain\Enums;

/**
 * The fixed set of site-wide roles, seeded once by RoleSeeder.
 */
enum RoleSlug: string
{
    case Admin = 'admin';
    case Moderator = 'moderator';
    case User = 'user';
}
