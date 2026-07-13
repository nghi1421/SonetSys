<?php

use App\Core\Auth\CoreAuthServiceProvider;
use App\Core\Tenancy\CoreTenancyServiceProvider;
use App\Modules\Feed\FeedServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    CoreAuthServiceProvider::class,
    CoreTenancyServiceProvider::class,
    FeedServiceProvider::class,
];
