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

    // Feed-owned moderation permissions. Architecturally these belong in a
    // Feed-scoped enum per module (see FeedServiceProvider's intent), but
    // TenantService (Core/Tenancy) would then need to import Feed's enum to
    // build tenantAdminDefaults() — a Core -> Module dependency we don't want
    // either. Kept here as a known MVP simplification until there's a proper
    // module permission registry that lets TenantService stay module-agnostic.
    case PostsDeleteAny = 'posts.delete.any';
    case CommentsDeleteAny = 'comments.delete.any';

    // Menu-owned permission, kept here for the same reason as the Feed-owned
    // ones above — avoids a Core -> Module dependency from TenantService.
    case MenuManage = 'menu.manage';

    // Group-owned moderation permission, kept here for the same reason as
    // the Feed-owned ones above.
    case GroupsManageAny = 'groups.manage.any';

    public function group(): string
    {
        return match ($this) {
            self::UsersView, self::UsersManage => 'users',
            self::RolesManage => 'roles',
            self::TenantsManage => 'tenants',
            self::PostsDeleteAny => 'posts',
            self::CommentsDeleteAny => 'comments',
            self::MenuManage => 'menu',
            self::GroupsManageAny => 'groups',
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
        return [
            self::UsersView,
            self::UsersManage,
            self::RolesManage,
            self::PostsDeleteAny,
            self::CommentsDeleteAny,
            self::MenuManage,
            self::GroupsManageAny,
        ];
    }
}
