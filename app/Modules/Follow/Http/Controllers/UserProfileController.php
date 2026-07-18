<?php

declare(strict_types=1);

namespace App\Modules\Follow\Http\Controllers;

use App\Core\Auth\Domain\Models\User;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Follow\Application\Services\FollowService;
use App\Modules\Follow\Http\Resources\UserProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UserProfileController extends Controller
{
    public function __construct(
        private readonly FollowService $follows,
    ) {}

    public function show(Request $request, User $user): JsonResponse
    {
        $viewerId = (int) $request->user()->id;

        $user->followers_count = $this->follows->followersCount($user->id);
        $user->following_count = $this->follows->followingCount($user->id);
        $user->is_following = $viewerId !== $user->id && $this->follows->isFollowing($viewerId, $user->id);
        $user->is_followed_by = $viewerId !== $user->id && $this->follows->isFollowing($user->id, $viewerId);

        return ApiResponse::success(UserProfileResource::make($user));
    }
}
