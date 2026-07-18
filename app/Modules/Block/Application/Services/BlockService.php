<?php

declare(strict_types=1);

namespace App\Modules\Block\Application\Services;

use App\Modules\Block\Application\Contracts\BlockRepositoryInterface;
use App\Modules\Block\Domain\Models\Block;
use App\Modules\Feed\Application\Support\FeedCache;
use App\Modules\Follow\Application\Contracts\FollowRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Depends on FollowRepositoryInterface directly (not FollowService) so that
 * Follow never needs to depend back on Block — a Follow<->Block cycle is
 * avoided by keeping FollowService pure and composing the "can't follow a
 * blocked user" check in FollowController instead. See the plan's
 * circular-dependency note for the full rationale.
 */
final class BlockService
{
    public function __construct(
        private readonly BlockRepositoryInterface $blocks,
        private readonly FollowRepositoryInterface $follows,
        private readonly FeedCache $feedCache,
    ) {}

    public function block(int $blockerId, int $blockedId): Block
    {
        if ($blockerId === $blockedId) {
            throw ValidationException::withMessages([
                'user' => 'You cannot block yourself.',
            ]);
        }

        $block = $this->blocks->block($blockerId, $blockedId);

        // Either user could currently follow the other — force-unfollow both
        // directions so the relationship is fully severed.
        $this->follows->unfollow($blockerId, $blockedId);
        $this->follows->unfollow($blockedId, $blockerId);

        $this->feedCache->forgetFeed();

        return $block;
    }

    public function unblock(int $blockerId, int $blockedId): void
    {
        $this->blocks->unblock($blockerId, $blockedId);
        $this->feedCache->forgetFeed();
    }

    public function isBlocked(int $blockerId, int $blockedId): bool
    {
        return $this->blocks->isBlocked($blockerId, $blockedId);
    }

    public function isBlockedEitherWay(int $a, int $b): bool
    {
        return $this->isBlocked($a, $b) || $this->isBlocked($b, $a);
    }

    /** @return Collection<int, Block> */
    public function listBlocked(int $blockerId): Collection
    {
        return $this->blocks->listBlocked($blockerId);
    }
}
