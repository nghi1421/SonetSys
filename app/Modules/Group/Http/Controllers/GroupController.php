<?php

declare(strict_types=1);

namespace App\Modules\Group\Http\Controllers;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Auth\Domain\Models\User;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Group\Application\Services\GroupService;
use App\Modules\Group\Domain\Models\Group;
use App\Modules\Group\Http\Requests\CreateGroupRequest;
use App\Modules\Group\Http\Requests\UpdateGroupRequest;
use App\Modules\Group\Http\Resources\GroupResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GroupController extends Controller
{
    public function __construct(
        private readonly GroupService $groups,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $groups = $this->groups->listForTenant((int) $user->tenant_id);
        $this->groups->attachViewerMembership($groups, (int) $user->id);

        return ApiResponse::success(GroupResource::collection($groups));
    }

    public function store(CreateGroupRequest $request): JsonResponse
    {
        $group = $this->groups->create($request->toDto());
        $this->groups->attachViewerMembership($group, (int) $request->user()->id);

        return ApiResponse::success(GroupResource::make($group->load('owner')), status: 201);
    }

    public function show(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();
        $this->ensureSameTenant($group, $user);

        $this->groups->attachViewerMembership($group, (int) $user->id);

        return ApiResponse::success(GroupResource::make($group->load('owner')));
    }

    public function update(UpdateGroupRequest $request, Group $group): JsonResponse
    {
        $user = $request->user();
        $this->ensureSameTenant($group, $user);
        $this->authorizeOwnerOrManager($group, $user);

        $group = $this->groups->update($group, $request->toDto());

        return ApiResponse::success(GroupResource::make($group->load('owner')));
    }

    public function destroy(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();
        $this->ensureSameTenant($group, $user);
        $this->authorizeOwnerOrManager($group, $user);

        $this->groups->delete($group);

        return ApiResponse::success();
    }

    private function ensureSameTenant(Group $group, User $user): void
    {
        if ($group->tenant_id !== $user->tenant_id) {
            throw new ModelNotFoundException;
        }
    }

    private function authorizeOwnerOrManager(Group $group, User $user): void
    {
        if ($group->owner_id !== $user->id && ! $user->hasPermission(PermissionSlug::GroupsManageAny->value)) {
            throw new AuthorizationException('You do not have permission to manage this group.');
        }
    }
}
