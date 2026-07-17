<?php

declare(strict_types=1);

namespace App\Modules\Follow\Application\Services;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Follow\Application\Contracts\FollowRepositoryInterface;
use App\Modules\Follow\Domain\Events\UserFollowed;
use App\Modules\Follow\Domain\Models\Follow;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class FollowService
{
    public function __construct(
        private readonly FollowRepositoryInterface $follows,
    ) {}

    public function follow(int $followerId, int $followedId): Follow
    {
        if ($followerId === $followedId) {
            throw ValidationException::withMessages([
                'user' => 'You cannot follow yourself.',
            ]);
        }

        $follow = $this->follows->follow($followerId, $followedId);

        if ($follow->wasRecentlyCreated) {
            UserFollowed::dispatch($followerId, $followedId);
        }

        return $follow;
    }

    public function unfollow(int $followerId, int $followedId): void
    {
        $this->follows->unfollow($followerId, $followedId);
    }

    public function isFollowing(int $followerId, int $followedId): bool
    {
        return $this->follows->isFollowing($followerId, $followedId);
    }

    public function followersCount(int $userId): int
    {
        return $this->follows->followersCount($userId);
    }

    public function followingCount(int $userId): int
    {
        return $this->follows->followingCount($userId);
    }

    /** @return Collection<int, Follow> */
    public function listFollowers(int $userId): Collection
    {
        return $this->follows->listFollowers($userId);
    }

    /** @return Collection<int, Follow> */
    public function listFollowing(int $userId): Collection
    {
        return $this->follows->listFollowing($userId);
    }

    /**
     * @param  Collection<int, User>  $users
     */
    public function markFollowingByViewer(Collection $users, int $viewerId): void
    {
        $followedIds = $this->follows->followedIdsAmong($viewerId, $users->pluck('id')->all());

        $users->each(function (User $user) use ($followedIds): void {
            $user->is_following = in_array($user->id, $followedIds, true);
        });
    }
}
