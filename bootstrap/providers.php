<?php

use App\Core\Auth\CoreAuthServiceProvider;
use App\Core\Storage\CoreStorageServiceProvider;
use App\Modules\Feed\FeedServiceProvider;
use App\Modules\Group\GroupServiceProvider;
use App\Modules\Menu\MenuServiceProvider;
use App\Modules\Notification\NotificationServiceProvider;
use App\Modules\Story\StoryServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    CoreAuthServiceProvider::class,
    CoreStorageServiceProvider::class,
    FeedServiceProvider::class,
    GroupServiceProvider::class,
    MenuServiceProvider::class,
    NotificationServiceProvider::class,
    StoryServiceProvider::class,
];
