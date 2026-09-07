<?php

declare(strict_types=1);

namespace App\Core\Auth\Domain\Enums;

enum PermissionSlug: string
{
    case UsersView = 'users.view';
    case UsersManage = 'users.manage';
    case RolesManage = 'roles.manage';

    case PostsDeleteAny = 'posts.delete.any';
    case CommentsDeleteAny = 'comments.delete.any';

    case MenuManage = 'menu.manage';

    case GroupsManageAny = 'groups.manage.any';

    case StorageManage = 'storage.manage';

    case SettingsManage = 'settings.manage';

    case WalletManage = 'wallet.manage';

    case AdsReview = 'ads.review';

    case ReportsReview = 'reports.review';

    case ReactionsManage = 'reactions.manage';

    case SubscriptionManage = 'subscription.manage';

    case SongsManage = 'songs.manage';

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
            self::ReactionsManage => 'reactions',
            self::SubscriptionManage => 'subscription',
            self::SongsManage => 'songs',
        };
    }

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
            self::ReactionsManage,
            self::SubscriptionManage,
            self::SongsManage,
        ];
    }

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
