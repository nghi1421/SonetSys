<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Follow\Application\Services\FollowService;
use App\Modules\Follow\Domain\Models\Follow;
use Illuminate\Support\Collection;

final class DemoSocialGraphStep
{
    private const MIN_FOLLOWING = 5;

    private const MAX_FOLLOWING = 16;

    public function __construct(
        private readonly FollowService $follows,
    ) {}

    /**
     * @param  Collection<int, User>  $users
     * @return list<array{0: int, 1: int}> mutual-follow pairs, for the chat step
     */
    public function run(Collection $users): array
    {
        $ids = $users->pluck('id')->all();

        foreach ($ids as $followerId) {
            $followeeCount = fake()->numberBetween(self::MIN_FOLLOWING, self::MAX_FOLLOWING);
            $followeeIds = collect($ids)
                ->reject(fn (int $id) => $id === $followerId)
                ->shuffle()
                ->take($followeeCount);

            foreach ($followeeIds as $followedId) {
                $this->follows->follow($followerId, $followedId);
            }
        }

        return $this->mutualPairsAmong($ids);
    }

    /**
     * @param  list<int>  $ids
     * @return list<array{0: int, 1: int}>
     */
    private function mutualPairsAmong(array $ids): array
    {
        $edges = Follow::query()
            ->whereIn('follower_id', $ids)
            ->whereIn('followed_id', $ids)
            ->get(['follower_id', 'followed_id'])
            ->map(fn (Follow $f) => (int) $f->follower_id.':'.(int) $f->followed_id)
            ->flip();

        $pairs = [];

        foreach ($ids as $a) {
            foreach ($ids as $b) {
                if ($a < $b && $edges->has("{$a}:{$b}") && $edges->has("{$b}:{$a}")) {
                    $pairs[] = [$a, $b];
                }
            }
        }

        return $pairs;
    }
}
