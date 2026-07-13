<?php

use App\Core\Auth\CoreAuthServiceProvider;
use App\Core\Tenancy\CoreTenancyServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    CoreAuthServiceProvider::class,
    CoreTenancyServiceProvider::class,
];
