<?php

declare(strict_types=1);

namespace App\Modules\Menu;

use App\Core\Tenancy\Domain\Events\TenantCreated;
use App\Modules\Menu\Application\Contracts\MenuItemRepositoryInterface;
use App\Modules\Menu\Application\Contracts\StaticPageRepositoryInterface;
use App\Modules\Menu\Application\Listeners\SeedDefaultMenuItems;
use App\Modules\Menu\Infrastructure\Repositories\EloquentMenuItemRepository;
use App\Modules\Menu\Infrastructure\Repositories\EloquentStaticPageRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class MenuServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MenuItemRepositoryInterface::class, EloquentMenuItemRepository::class);
        $this->app->bind(StaticPageRepositoryInterface::class, EloquentStaticPageRepository::class);
    }

    public function boot(): void
    {
        // Menu listens to Tenancy's event — Core/Tenancy never references
        // Menu, keeping the Core -> Module dependency direction intact.
        Event::listen(TenantCreated::class, SeedDefaultMenuItems::class);
    }
}
