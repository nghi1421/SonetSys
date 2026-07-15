<?php

declare(strict_types=1);

namespace App\Modules\Menu\Application\Services;

use App\Modules\Menu\Application\Contracts\MenuItemRepositoryInterface;
use App\Modules\Menu\Application\DTOs\CreateMenuItemData;
use App\Modules\Menu\Application\DTOs\UpdateMenuItemData;
use App\Modules\Menu\Domain\Models\MenuItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class MenuService
{
    public function __construct(
        private readonly MenuItemRepositoryInterface $items,
    ) {}

    /**
     * @return Collection<int, MenuItem>
     */
    public function listForTenant(int $tenantId): Collection
    {
        return $this->items->listForTenant($tenantId);
    }

    public function findBySlug(int $tenantId, string $slug): ?MenuItem
    {
        return $this->items->findBySlug($tenantId, $slug);
    }

    public function create(CreateMenuItemData $data): MenuItem
    {
        return $this->items->create([
            'tenant_id' => $data->tenantId,
            'label' => $data->label,
            'slug' => $this->uniqueSlug($data->tenantId, Str::slug($data->label)),
            'position' => $this->items->countForTenant($data->tenantId),
            'is_home' => false,
            'static_page_id' => $data->staticPageId,
        ]);
    }

    public function update(MenuItem $item, UpdateMenuItemData $data): MenuItem
    {
        return $this->items->update($item, [
            'label' => $data->label,
            'static_page_id' => $data->staticPageId,
        ]);
    }

    public function delete(MenuItem $item): void
    {
        $this->items->delete($item);
    }

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(int $tenantId, array $orderedIds): void
    {
        $this->items->reorder($tenantId, $orderedIds);
    }

    public function seedDefaults(int $tenantId): void
    {
        $this->items->create([
            'tenant_id' => $tenantId,
            'label' => 'Home',
            'slug' => 'home',
            'position' => 0,
            'is_home' => true,
            'static_page_id' => null,
        ]);

        $this->items->create([
            'tenant_id' => $tenantId,
            'label' => 'Group',
            'slug' => 'group',
            'position' => 1,
            'is_home' => false,
            'static_page_id' => null,
        ]);

        $this->items->create([
            'tenant_id' => $tenantId,
            'label' => 'Advertise',
            'slug' => 'advertise',
            'position' => 2,
            'is_home' => false,
            'static_page_id' => null,
        ]);
    }

    private function uniqueSlug(int $tenantId, string $base): string
    {
        $slug = $base;
        $suffix = 2;

        while ($this->items->findBySlug($tenantId, $slug) !== null) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
