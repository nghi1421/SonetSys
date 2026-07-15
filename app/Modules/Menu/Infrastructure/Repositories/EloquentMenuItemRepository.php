<?php

declare(strict_types=1);

namespace App\Modules\Menu\Infrastructure\Repositories;

use App\Modules\Menu\Application\Contracts\MenuItemRepositoryInterface;
use App\Modules\Menu\Domain\Models\MenuItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentMenuItemRepository implements MenuItemRepositoryInterface
{
    public function create(array $attributes): MenuItem
    {
        return MenuItem::query()->create($attributes);
    }

    public function findById(int $id): ?MenuItem
    {
        return MenuItem::query()->find($id);
    }

    public function findBySlug(int $tenantId, string $slug): ?MenuItem
    {
        return MenuItem::query()
            ->where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->first();
    }

    public function listForTenant(int $tenantId): Collection
    {
        return MenuItem::query()
            ->where('tenant_id', $tenantId)
            ->with('staticPage')
            ->orderBy('position')
            ->get();
    }

    public function update(MenuItem $item, array $attributes): MenuItem
    {
        $item->fill($attributes)->save();

        return $item;
    }

    public function delete(MenuItem $item): void
    {
        $item->delete();
    }

    public function countForTenant(int $tenantId): int
    {
        return MenuItem::query()->where('tenant_id', $tenantId)->count();
    }

    public function reorder(int $tenantId, array $orderedIds): void
    {
        DB::transaction(function () use ($tenantId, $orderedIds): void {
            foreach ($orderedIds as $position => $id) {
                MenuItem::query()
                    ->where('tenant_id', $tenantId)
                    ->where('id', $id)
                    ->update(['position' => $position]);
            }
        });
    }
}
