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
    public function list(): Collection
    {
        return $this->items->list();
    }

    public function findBySlug(string $slug): ?MenuItem
    {
        return $this->items->findBySlug($slug);
    }

    public function create(CreateMenuItemData $data): MenuItem
    {
        return $this->items->create([
            'label' => $data->label,
            'slug' => $this->uniqueSlug(Str::slug($data->label)),
            'position' => $this->items->count(),
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
    public function reorder(array $orderedIds): void
    {
        $this->items->reorder($orderedIds);
    }

    public function seedDefaults(): void
    {
        if ($this->items->count() > 0) {
            return;
        }

        $this->items->create([
            'label' => 'Home',
            'slug' => 'home',
            'position' => 0,
            'is_home' => true,
            'static_page_id' => null,
        ]);

        $this->items->create([
            'label' => 'Group',
            'slug' => 'group',
            'position' => 1,
            'is_home' => false,
            'static_page_id' => null,
        ]);

        $this->items->create([
            'label' => 'Advertise',
            'slug' => 'advertise',
            'position' => 2,
            'is_home' => false,
            'static_page_id' => null,
        ]);

        $this->items->create([
            'label' => 'Subscription',
            'slug' => 'subscription',
            'position' => 3,
            'is_home' => false,
            'static_page_id' => null,
        ]);
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $suffix = 2;

        while ($this->items->findBySlug($slug) !== null) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
