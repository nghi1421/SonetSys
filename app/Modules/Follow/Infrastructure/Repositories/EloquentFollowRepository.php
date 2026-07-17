<?php

declare(strict_types=1);

namespace App\Modules\Follow\Infrastructure\Repositories;

use App\Modules\Follow\Application\Contracts\FollowRepositoryInterface;
use App\Modules\Follow\Domain\Models\Follow;
use Illuminate\Support\Collection;

final class EloquentFollowRepository implements FollowRepositoryInterface
{
    public function follow(int $followerId, int $followedId): Follow
    {
        return Follow::query()->firstOrCreate([
            'follower_id' => $followerId,
            'followed_id' => $followedId,
        ]);
    }

    public function unfollow(int $followerId, int $followedId): void
    {
        Follow::query()
            ->where('follower_id', $followerId)
            ->where('followed_id', $followedId)
            ->delete();
    }

    public function isFollowing(int $followerId, int $followedId): bool
    {
        return Follow::query()
            ->where('follower_id', $followerId)
            ->where('followed_id', $followedId)
            ->exists();
    }

    public function followersCount(int $userId): int
    {
        return Follow::query()->where('followed_id', $userId)->count();
    }

    public function followingCount(int $userId): int
    {
        return Follow::query()->where('follower_id', $userId)->count();
    }

    public function listFollowers(int $userId): Collection
    {
        return Follow::query()
            ->where('followed_id', $userId)
            ->with('follower')
            ->orderByDesc('created_at')
            ->get();
    }

    public function listFollowing(int $userId): Collection
    {
        return Follow::query()
            ->where('follower_id', $userId)
            ->with('followed')
            ->orderByDesc('created_at')
            ->get();
    }

    public function followedIdsAmong(int $followerId, array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return Follow::query()
            ->where('follower_id', $followerId)
            ->whereIn('followed_id', $userIds)
            ->pluck('followed_id')
            ->all();
    }
}
