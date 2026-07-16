<?php

declare(strict_types=1);

namespace App\Modules\Group\Http\Controllers;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Auth\Domain\Models\User;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Group\Application\Services\GroupService;
use App\Modules\Group\Domain\Enums\GroupMemberRole;
use App\Modules\Group\Domain\Enums\GroupMemberStatus;
use App\Modules\Group\Domain\Models\Group;
use App\Modules\Group\Http\Resources\GroupMemberResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GroupMembershipController extends Controller
{
    public function __construct(
        private readonly GroupService $groups,
    ) {}

    public function join(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        $member = $this->groups->requestJoin($group, (int) $user->id);

        return ApiResponse::success(GroupMemberResource::make($member->load('user')));
    }

    public function leave(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        if ($group->owner_id === $user->id) {
            throw new AuthorizationException('Transfer ownership or delete the group instead of leaving.');
        }

        $this->groups->removeMember($group, (int) $user->id);

        return ApiResponse::success();
    }

    public function members(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();
        $this->ensureApprovedMember($group, $user);

        $members = $this->groups->listMembers($group, GroupMemberStatus::Approved->value);

        return ApiResponse::success(GroupMemberResource::collection($members));
    }

    public function requests(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();
        $this->authorizeOwnerOrManager($group, $user);

        $members = $this->groups->listMembers($group, GroupMemberStatus::Pending->value);

        return ApiResponse::success(GroupMemberResource::collection($members));
    }

    public function approve(Request $request, Group $group, User $user): JsonResponse
    {
        $actingUser = $request->user();
        $this->authorizeOwnerOrManager($group, $actingUser);

        $member = $this->groups->approveMember($group, (int) $user->id);

        return ApiResponse::success(GroupMemberResource::make($member->load('user')));
    }

    public function remove(Request $request, Group $group, User $user): JsonResponse
    {
        $actingUser = $request->user();
        $this->authorizeOwnerOrManager($group, $actingUser);

        if ($group->owner_id === $user->id) {
            throw new AuthorizationException('The group owner cannot be removed.');
        }

        $targetMembership = $this->groups->membershipFor($group, (int) $user->id);

        if ($targetMembership !== null
            && $targetMembership->role === GroupMemberRole::Admin
            && $group->owner_id !== $actingUser->id
            && ! $actingUser->hasPermission(PermissionSlug::GroupsManageAny->value)
        ) {
            throw new AuthorizationException('Only the owner can remove an admin.');
        }

        $this->groups->removeMember($group, (int) $user->id);

        return ApiResponse::success();
    }

    public function promote(Request $request, Group $group, User $user): JsonResponse
    {
        $actingUser = $request->user();
        $this->authorizeOwner($group, $actingUser);

        $member = $this->groups->promoteToAdmin($group, (int) $user->id);

        return ApiResponse::success(GroupMemberResource::make($member->load('user')));
    }

    public function demote(Request $request, Group $group, User $user): JsonResponse
    {
        $actingUser = $request->user();
        $this->authorizeOwner($group, $actingUser);

        $member = $this->groups->demoteToMember($group, (int) $user->id);

        return ApiResponse::success(GroupMemberResource::make($member->load('user')));
    }

    private function ensureApprovedMember(Group $group, User $user): void
    {
        if ($group->owner_id === $user->id) {
            return;
        }

        $membership = $this->groups->membershipFor($group, (int) $user->id);

        if ($membership === null || $membership->status !== GroupMemberStatus::Approved) {
            throw new AuthorizationException('You must be a member of this group.');
        }
    }

    private function authorizeOwnerOrManager(Group $group, User $user): void
    {
        if (! $this->groups->isManager($group, (int) $user->id) && ! $user->hasPermission(PermissionSlug::GroupsManageAny->value)) {
            throw new AuthorizationException('You do not have permission to manage this group.');
        }
    }

    private function authorizeOwner(Group $group, User $user): void
    {
        if ($group->owner_id !== $user->id && ! $user->hasPermission(PermissionSlug::GroupsManageAny->value)) {
            throw new AuthorizationException('Only the group owner can do this.');
        }
    }
}
