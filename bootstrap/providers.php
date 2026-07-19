<?php

use App\Core\Auth\CoreAuthServiceProvider;
use App\Core\Storage\CoreStorageServiceProvider;
use App\Modules\Advertising\AdvertisingServiceProvider;
use App\Modules\Block\BlockServiceProvider;
use App\Modules\Chat\ChatServiceProvider;
use App\Modules\Feed\FeedServiceProvider;
use App\Modules\Follow\FollowServiceProvider;
use App\Modules\Group\GroupServiceProvider;
use App\Modules\Menu\MenuServiceProvider;
use App\Modules\Notification\NotificationServiceProvider;
use App\Modules\Report\ReportServiceProvider;
use App\Modules\Search\SearchServiceProvider;
use App\Modules\Story\StoryServiceProvider;
use App\Modules\Wallet\WalletServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    CoreAuthServiceProvider::class,
    CoreStorageServiceProvider::class,
    AdvertisingServiceProvider::class,
    BlockServiceProvider::class,
    ChatServiceProvider::class,
    FeedServiceProvider::class,
    FollowServiceProvider::class,
    GroupServiceProvider::class,
    MenuServiceProvider::class,
    NotificationServiceProvider::class,
    ReportServiceProvider::class,
    SearchServiceProvider::class,
    StoryServiceProvider::class,
    WalletServiceProvider::class,
];
