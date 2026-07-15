<?php

declare(strict_types=1);

namespace App\Modules\Group\Infrastructure\Repositories;

use App\Modules\Feed\Application\Contracts\GroupAccessCheckerInterface;
use App\Modules\Group\Application\Services\GroupService;
use App\Modules\Group\Domain\Enums\GroupMemberStatus;
use App\Modules\Group\Domain\Enums\GroupVisibility;
use App\Modules\Group\Domain\Models\Group;

final class GroupAccessChecker implements GroupAccessCheckerInterface
{
    public function __construct(
        private readonly GroupService $groups,
    ) {}

    public function canView(int $groupId, int $userId): bool
    {
        $group = $this->groups->findById($groupId);

        if ($group === null) {
            return false;
        }

        if ($group->visibility === GroupVisibility::Public) {
            return true;
        }

        return $this->isApprovedMember($group, $userId);
    }

    public function canInteract(int $groupId, int $userId): bool
    {
        $group = $this->groups->findById($groupId);

        if ($group === null) {
            return false;
        }

        return $this->isApprovedMember($group, $userId);
    }

    private function isApprovedMember(Group $group, int $userId): bool
    {
        if ($group->owner_id === $userId) {
            return true;
        }

        $membership = $this->groups->membershipFor($group, $userId);

        return $membership !== null && $membership->status === GroupMemberStatus::Approved;
    }
}
