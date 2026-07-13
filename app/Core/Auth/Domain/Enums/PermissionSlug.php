<?php

declare(strict_types=1);

namespace App\Core\Auth\Domain\Enums;

/**
 * Baseline permission catalog owned by Core/Auth and Core/Tenancy.
 * Feature modules (Feed, Notification, ...) register their own slugs
 * into the same `permissions` table on boot — this enum only covers
 * the permissions Core itself depends on.
 */
enum PermissionSlug: string
{
    case UsersView = 'users.view';
    case UsersManage = 'users.manage';
    case RolesManage = 'roles.manage';
    case TenantsManage = 'tenants.manage';

    public function group(): string
    {
        return match ($this) {
            self::UsersView, self::UsersManage => 'users',
            self::RolesManage => 'roles',
            self::TenantsManage => 'tenants',
        };
    }

    /**
     * Default permission set granted to a tenant's seeded TenantAdmin role.
     * Single source of truth for TenantService::create() and RoleFactory::tenantAdmin().
     *
     * @return list<self>
     */
    public static function tenantAdminDefaults(): array
    {
        return [self::UsersView, self::UsersManage, self::RolesManage];
    }
}
