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

    public function findBySlug(string $slug): ?Group
    {
        return Group::query()->where('slug', $slug)->first();
    }

    public function list(): Collection
    {
        return Group::query()
            ->with('owner')
            ->orderByDesc('created_at')
            ->get();
    }

    public function popular(int $limit): Collection
    {
        return Group::query()
            ->with('owner')
            ->orderByDesc('members_count')
            ->limit($limit)
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

    public function count(): int
    {
        return Group::query()->count();
    }
}
