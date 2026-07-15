<?php

declare(strict_types=1);

namespace App\Modules\Menu\Application\Contracts;

use App\Modules\Menu\Domain\Models\MenuItem;
use Illuminate\Support\Collection;

interface MenuItemRepositoryInterface
{
    public function create(array $attributes): MenuItem;

    public function findById(int $id): ?MenuItem;

    public function findBySlug(int $tenantId, string $slug): ?MenuItem;

    /**
     * @return Collection<int, MenuItem>
     */
    public function listForTenant(int $tenantId): Collection;

    public function update(MenuItem $item, array $attributes): MenuItem;

    public function delete(MenuItem $item): void;

    public function countForTenant(int $tenantId): int;

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(int $tenantId, array $orderedIds): void;
}
