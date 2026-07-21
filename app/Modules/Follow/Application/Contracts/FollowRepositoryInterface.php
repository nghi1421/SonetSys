<?php

declare(strict_types=1);

namespace App\Modules\Follow\Application\Contracts;

use App\Modules\Follow\Domain\Models\Follow;
use Illuminate\Support\Collection;

interface FollowRepositoryInterface
{
    public function follow(int $followerId, int $followedId): Follow;

    public function unfollow(int $followerId, int $followedId): void;

    public function isFollowing(int $followerId, int $followedId): bool;

    public function followersCount(int $userId): int;

    public function followingCount(int $userId): int;

    /** @return Collection<int, Follow> */
    public function listFollowers(int $userId): Collection;

    /** @return Collection<int, Follow> */
    public function listFollowing(int $userId): Collection;

    /**
     * @param  list<int>  $userIds
     * @return list<int>
     */
    public function followedIdsAmong(int $followerId, array $userIds): array;
}
