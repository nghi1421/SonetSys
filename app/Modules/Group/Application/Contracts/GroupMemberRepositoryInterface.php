<?php

declare(strict_types=1);

namespace App\Modules\Group\Application\Contracts;

use App\Modules\Group\Domain\Models\GroupMember;
use Illuminate\Support\Collection;

interface GroupMemberRepositoryInterface
{
    public function create(array $attributes): GroupMember;

    public function findForGroupAndUser(int $groupId, int $userId): ?GroupMember;

    /**
     * @return Collection<int, GroupMember>
     */
    public function listForGroup(int $groupId, ?string $status = null): Collection;

    /**
     * @param  list<int>  $groupIds
     * @return Collection<int, GroupMember>
     */
    public function listForUserAndGroups(int $userId, array $groupIds): Collection;

    public function update(GroupMember $member, array $attributes): GroupMember;

    public function delete(GroupMember $member): void;
}
