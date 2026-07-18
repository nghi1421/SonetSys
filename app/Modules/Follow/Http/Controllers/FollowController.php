<?php

declare(strict_types=1);

namespace App\Modules\Follow\Http\Controllers;

use App\Core\Auth\Domain\Models\User;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Block\Application\Services\BlockService;
use App\Modules\Follow\Application\Services\FollowService;
use App\Modules\Follow\Domain\Models\Follow;
use App\Modules\Follow\Http\Resources\FollowUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class FollowController extends Controller
{
    public function __construct(
        private readonly FollowService $follows,
        private readonly BlockService $blocks,
    ) {}

    public function store(Request $request, User $user): JsonResponse
    {
        $followerId = (int) $request->user()->id;

        // Kept out of FollowService itself: Block talks to Follow's
        // repository directly (not FollowService) to avoid a circular
        // module dependency, so the "can't follow a blocked user" check is
        // composed here at the HTTP layer instead.
        if ($this->blocks->isBlockedEitherWay($followerId, $user->id)) {
            throw ValidationException::withMessages([
                'user' => 'You cannot follow this user.',
            ]);
        }

        $this->follows->follow($followerId, $user->id);

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
