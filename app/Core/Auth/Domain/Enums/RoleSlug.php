<?php

declare(strict_types=1);

namespace App\Core\Auth\Domain\Enums;

/**
 * Baseline seeded roles. The `roles` table remains an open catalog —
 * tenants may define additional custom roles beyond these three.
 */
enum RoleSlug: string
{
    case SuperAdmin = 'super-admin';
    case TenantAdmin = 'tenant-admin';
    case Member = 'member';
}
