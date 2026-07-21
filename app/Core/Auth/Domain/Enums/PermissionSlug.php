<?php

declare(strict_types=1);

namespace App\Core\Auth\Domain\Enums;

/**
 * Baseline permission catalog owned by Core/Auth. Feature modules (Feed,
 * Notification, ...) register their own slugs into the same `permissions`
 * table on boot — this enum only covers the permissions Core itself
 * depends on.
 */
enum PermissionSlug: string
{
    case UsersView = 'users.view';
    case UsersManage = 'users.manage';
    case RolesManage = 'roles.manage';

    // Feed-owned moderation permissions. Architecturally these belong in a
    // Feed-scoped enum per module (see FeedServiceProvider's intent), but
    // RoleSeeder would then need to import Feed's enum to build
    // adminDefaults()/moderatorDefaults() — a Core -> Module dependency we
    // don't want either. Kept here as a known simplification until there's
    // a proper module permission registry that lets RoleSeeder stay
    // module-agnostic.
    case PostsDeleteAny = 'posts.delete.any';
    case CommentsDeleteAny = 'comments.delete.any';

    // Menu-owned permission, kept here for the same reason as the Feed-owned
    // ones above — avoids a Core -> Module dependency from RoleSeeder.
    case MenuManage = 'menu.manage';

    // Group-owned moderation permission, kept here for the same reason as
    // the Feed-owned ones above.
    case GroupsManageAny = 'groups.manage.any';

    // Storage is a Core module, so this could live in its own enum without
    // the Core -> Module dependency problem the cases above work around —
    // kept here anyway for a single source of truth alongside the others.
    case StorageManage = 'storage.manage';

    // System settings (mail server, cache driver, Redis connection, max
    // upload size) is a Core module, kept here for the same single-source-
    // of-truth reason as StorageManage.
    case SettingsManage = 'settings.manage';

    // Wallet is a feature module; kept here for the same Core -> Module
    // dependency reason as the cases above, so RoleSeeder stays
    // module-agnostic.
    case WalletManage = 'wallet.manage';

    // Advertising is a feature module; kept here for the same Core -> Module
    // dependency reason as the cases above, so RoleSeeder stays
    // module-agnostic.
    case AdsReview = 'ads.review';

    // Report is a feature module; kept here for the same Core -> Module
    // dependency reason as the cases above. Unlike AdsReview/WalletManage,
    // this is deliberately granted to Moderator as well as Admin — reviewing
    // reports is core day-to-day moderation work, the same reasoning already
    // applied to PostsDeleteAny/CommentsDeleteAny.
    case ReportsReview = 'reports.review';

    public function group(): string
    {
        return match ($this) {
            self::UsersView, self::UsersManage => 'users',
            self::RolesManage => 'roles',
            self::PostsDeleteAny => 'posts',
            self::CommentsDeleteAny => 'comments',
            self::MenuManage => 'menu',
            self::GroupsManageAny => 'groups',
            self::StorageManage => 'storage',
            self::SettingsManage => 'settings',
            self::WalletManage => 'wallet',
            self::AdsReview => 'ads',
            self::ReportsReview => 'reports',
        };
    }

    /**
     * Default permission set granted to the seeded Admin role — a full site
     * operator. Single source of truth for RoleSeeder and RoleFactory::admin().
     *
     * @return list<self>
     */
    public static function adminDefaults(): array
    {
        return [
            self::UsersView,
            self::UsersManage,
            self::RolesManage,
            self::PostsDeleteAny,
            self::CommentsDeleteAny,
            self::MenuManage,
            self::GroupsManageAny,
            self::StorageManage,
            self::SettingsManage,
            self::WalletManage,
            self::AdsReview,
            self::ReportsReview,
        ];
    }

    /**
     * Default permission set granted to the seeded Moderator role — content
     * moderation only, no user/menu/storage management. Single source of
     * truth for RoleSeeder and RoleFactory::moderator().
     *
     * @return list<self>
     */
    public static function moderatorDefaults(): array
    {
        return [
            self::PostsDeleteAny,
            self::CommentsDeleteAny,
            self::GroupsManageAny,
            self::ReportsReview,
        ];
    }
}
