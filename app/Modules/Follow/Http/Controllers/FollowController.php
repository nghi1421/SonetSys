<?php

declare(strict_types=1);

namespace App\Modules\Follow\Http\Controllers;

use App\Core\Auth\Domain\Models\User;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Follow\Application\Services\FollowService;
use App\Modules\Follow\Domain\Models\Follow;
use App\Modules\Follow\Http\Resources\FollowUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FollowController extends Controller
{
    public function __construct(
        private readonly FollowService $follows,
    ) {}

    public function store(Request $request, User $user): JsonResponse
    {
        $this->follows->follow((int) $request->user()->id, $user->id);

        return ApiResponse::success();
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->follows->unfollow((int) $request->user()->id, $user->id);

        return ApiResponse::success();
    }

    public function followers(Request $request, User $user): JsonResponse
    {
        $users = $this->follows->listFollowers($user->id)
            ->map(fn (Follow $follow) => $follow->follower);

        $this->follows->markFollowingByViewer($users, (int) $request->user()->id);

        return ApiResponse::success(FollowUserResource::collection($users));
    }

    public function following(Request $request, User $user): JsonResponse
    {
        $users = $this->follows->listFollowing($user->id)
            ->map(fn (Follow $follow) => $follow->followed);

        $this->follows->markFollowingByViewer($users, (int) $request->user()->id);

        return ApiResponse::success(FollowUserResource::collection($users));
    }
}
