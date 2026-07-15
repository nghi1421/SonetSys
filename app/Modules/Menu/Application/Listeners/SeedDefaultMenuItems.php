<?php

declare(strict_types=1);

namespace App\Modules\Menu\Application\Listeners;

use App\Core\Tenancy\Domain\Events\TenantCreated;
use App\Modules\Menu\Application\Services\MenuService;

final class SeedDefaultMenuItems
{
    public function __construct(
        private readonly MenuService $menu,
    ) {}

    public function handle(TenantCreated $event): void
    {
        $this->menu->seedDefaults($event->tenantId);
    }
}
