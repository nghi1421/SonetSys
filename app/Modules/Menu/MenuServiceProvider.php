<?php

declare(strict_types=1);

namespace App\Modules\Menu;

use App\Modules\Menu\Application\Contracts\MenuItemRepositoryInterface;
use App\Modules\Menu\Application\Contracts\StaticPageRepositoryInterface;
use App\Modules\Menu\Infrastructure\Repositories\EloquentMenuItemRepository;
use App\Modules\Menu\Infrastructure\Repositories\EloquentStaticPageRepository;
use Illuminate\Support\ServiceProvider;

final class MenuServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MenuItemRepositoryInterface::class, EloquentMenuItemRepository::class);
        $this->app->bind(StaticPageRepositoryInterface::class, EloquentStaticPageRepository::class);
    }
}
