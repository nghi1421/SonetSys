<?php

declare(strict_types=1);

namespace App\Modules\Group\Infrastructure\Repositories;

use App\Modules\Group\Application\Contracts\GroupRepositoryInterface;
use App\Modules\Group\Domain\Models\Group;
use Illuminate\Support\Collection;

final class EloquentGroupRepository implements GroupRepositoryInterface
{
    public function create(array $attributes): Group
    {
        return Group::query()->create($attributes)->refresh();
    }

    public function findById(int $id): ?Group
    {
        return Group::query()->find($id);
    }

    public function findBySlugForTenant(int $tenantId, string $slug): ?Group
    {
        return Group::query()
            ->where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->first();
    }

    public function listForTenant(int $tenantId): Collection
    {
        return Group::query()
            ->where('tenant_id', $tenantId)
            ->with('owner')
            ->orderByDesc('created_at')
            ->get();
    }

    public function update(Group $group, array $attributes): Group
    {
        $group->fill($attributes)->save();

        return $group;
    }

    public function delete(Group $group): void
    {
        $group->delete();
    }

    public function incrementMembersCount(int $groupId): void
    {
        Group::query()->whereKey($groupId)->increment('members_count');
    }

    public function decrementMembersCount(int $groupId): void
    {
        Group::query()->whereKey($groupId)->decrement('members_count');
    }
}
