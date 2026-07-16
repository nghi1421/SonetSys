<?php

declare(strict_types=1);

namespace App\Modules\Group\Infrastructure\Repositories;

use App\Modules\Group\Application\Contracts\GroupMemberRepositoryInterface;
use App\Modules\Group\Domain\Models\GroupMember;
use Illuminate\Support\Collection;

final class EloquentGroupMemberRepository implements GroupMemberRepositoryInterface
{
    public function create(array $attributes): GroupMember
    {
        return GroupMember::query()->create($attributes);
    }

    public function findForGroupAndUser(int $groupId, int $userId): ?GroupMember
    {
        return GroupMember::query()
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->first();
    }

    public function listForGroup(int $groupId, ?string $status = null): Collection
    {
        return GroupMember::query()
            ->where('group_id', $groupId)
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->with('user')
            ->orderBy('joined_at')
            ->get();
    }

    public function listForUserAndGroups(int $userId, array $groupIds): Collection
    {
        return GroupMember::query()
            ->where('user_id', $userId)
            ->whereIn('group_id', $groupIds)
            ->get();
    }

    public function update(GroupMember $member, array $attributes): GroupMember
    {
        $member->fill($attributes)->save();

        return $member;
    }

    public function delete(GroupMember $member): void
    {
        $member->delete();
    }
}
