<?php

declare(strict_types=1);

namespace App\Modules\Group\Application\Services;

use App\Modules\Group\Application\Contracts\GroupMemberRepositoryInterface;
use App\Modules\Group\Application\Contracts\GroupRepositoryInterface;
use App\Modules\Group\Application\DTOs\CreateGroupData;
use App\Modules\Group\Application\DTOs\UpdateGroupData;
use App\Modules\Group\Domain\Enums\GroupMemberRole;
use App\Modules\Group\Domain\Enums\GroupMemberStatus;
use App\Modules\Group\Domain\Enums\GroupVisibility;
use App\Modules\Group\Domain\Models\Group;
use App\Modules\Group\Domain\Models\GroupMember;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class GroupService
{
    public function __construct(
        private readonly GroupRepositoryInterface $groups,
        private readonly GroupMemberRepositoryInterface $members,
    ) {}

    public function create(CreateGroupData $data): Group
    {
        $group = $this->groups->create([
            'tenant_id' => $data->tenantId,
            'owner_id' => $data->ownerId,
            'name' => $data->name,
            'slug' => $this->uniqueSlug($data->tenantId, $data->name),
            'description' => $data->description,
            'visibility' => $data->visibility,
        ]);

        $this->members->create([
            'group_id' => $group->id,
            'user_id' => $data->ownerId,
            'role' => GroupMemberRole::Owner,
            'status' => GroupMemberStatus::Approved,
            'joined_at' => now(),
        ]);

        $this->groups->incrementMembersCount($group->id);

        return $group->refresh();
    }

    public function update(Group $group, UpdateGroupData $data): Group
    {
        return $this->groups->update($group, [
            'name' => $data->name,
            'description' => $data->description,
            'visibility' => $data->visibility,
        ]);
    }

    public function delete(Group $group): void
    {
        $this->groups->delete($group);
    }

    public function findBySlugForTenant(int $tenantId, string $slug): ?Group
    {
        return $this->groups->findBySlugForTenant($tenantId, $slug);
    }

    /**
     * @return Collection<int, Group>
     */
    public function listForTenant(int $tenantId): Collection
    {
        return $this->groups->listForTenant($tenantId);
    }

    /**
     * @param  Group|Collection<int, Group>  $groups
     */
    public function attachViewerMembership(Group|Collection $groups, int $viewerId): void
    {
        $collection = $groups instanceof Group ? collect([$groups]) : $groups;

        $memberships = $this->members
            ->listForUserAndGroups($viewerId, $collection->pluck('id')->all())
            ->keyBy('group_id');

        $collection->each(function (Group $group) use ($memberships): void {
            $group->viewer_membership = $memberships->get($group->id);
        });
    }

    public function membershipFor(Group $group, int $userId): ?GroupMember
    {
        return $this->members->findForGroupAndUser($group->id, $userId);
    }

    public function requestJoin(Group $group, int $userId): GroupMember
    {
        $existing = $this->members->findForGroupAndUser($group->id, $userId);

        if ($existing !== null) {
            return $existing;
        }

        $status = $group->visibility === GroupVisibility::Public
            ? GroupMemberStatus::Approved
            : GroupMemberStatus::Pending;

        $member = $this->members->create([
            'group_id' => $group->id,
            'user_id' => $userId,
            'role' => GroupMemberRole::Member,
            'status' => $status,
            'joined_at' => $status === GroupMemberStatus::Approved ? now() : null,
        ]);

        if ($status === GroupMemberStatus::Approved) {
            $this->groups->incrementMembersCount($group->id);
        }

        return $member;
    }

    public function approveMember(Group $group, int $targetUserId): GroupMember
    {
        $member = $this->members->findForGroupAndUser($group->id, $targetUserId);

        if ($member === null || $member->status === GroupMemberStatus::Approved) {
            throw new ModelNotFoundException;
        }

        $member = $this->members->update($member, [
            'status' => GroupMemberStatus::Approved,
            'joined_at' => now(),
        ]);

        $this->groups->incrementMembersCount($group->id);

        return $member;
    }

    public function removeMember(Group $group, int $targetUserId): void
    {
        $member = $this->members->findForGroupAndUser($group->id, $targetUserId);

        if ($member === null) {
            throw new ModelNotFoundException;
        }

        $wasApproved = $member->status === GroupMemberStatus::Approved;

        $this->members->delete($member);

        if ($wasApproved) {
            $this->groups->decrementMembersCount($group->id);
        }
    }

    /**
     * @return Collection<int, GroupMember>
     */
    public function listMembers(Group $group, ?string $status = null): Collection
    {
        return $this->members->listForGroup($group->id, $status);
    }

    private function uniqueSlug(int $tenantId, string $name): string
    {
        $base = Str::slug($name);
        $slug = $base !== '' ? $base : 'group';
        $suffix = 1;

        while ($this->groups->findBySlugForTenant($tenantId, $slug) !== null) {
            $suffix++;
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }
}
