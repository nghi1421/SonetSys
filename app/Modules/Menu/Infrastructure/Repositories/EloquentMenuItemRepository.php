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

    public function findBySlug(string $slug): ?MenuItem
    {
        return MenuItem::query()->where('slug', $slug)->first();
    }

    public function list(): Collection
    {
        return MenuItem::query()
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

    public function count(): int
    {
        return MenuItem::query()->count();
    }

    public function reorder(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $position => $id) {
                MenuItem::query()
                    ->where('id', $id)
                    ->update(['position' => $position]);
            }
        });
    }
}
