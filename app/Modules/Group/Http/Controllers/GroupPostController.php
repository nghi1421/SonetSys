<?php

declare(strict_types=1);

namespace App\Modules\Group\Http\Controllers;

use App\Core\Auth\Domain\Models\User;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Feed\Application\Services\PostService;
use App\Modules\Feed\Http\Resources\PostResource;
use App\Modules\Group\Application\Services\GroupService;
use App\Modules\Group\Domain\Enums\GroupMemberStatus;
use App\Modules\Group\Domain\Enums\GroupVisibility;
use App\Modules\Group\Domain\Models\Group;
use App\Modules\Group\Http\Requests\CreateGroupPostRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GroupPostController extends Controller
{
    public function __construct(
        private readonly GroupService $groups,
        private readonly PostService $posts,
    ) {}

    public function index(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        // Public groups are readable by any registered user — only posting
        // requires membership. Private groups gate both.
        if ($group->visibility !== GroupVisibility::Public) {
            $this->ensureApprovedMember($group, $user);
        }

        $limit = min((int) $request->query('limit', 20), 50);

        $result = $this->posts->feedForGroup($group->id, (int) $user->id, $request->query('cursor'), $limit);

        return ApiResponse::success(PostResource::collection($result['items']), [
            'next_cursor' => $result['next_cursor'],
        ]);
    }

    public function store(CreateGroupPostRequest $request, Group $group): JsonResponse
    {
        $user = $request->user();
        $this->ensureApprovedMember($group, $user);

        $post = $this->posts->create($request->toDto($group));

        return ApiResponse::success(PostResource::make($post->load('author')), status: 201);
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
}
