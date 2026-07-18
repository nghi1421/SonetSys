<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\Services;

use App\Modules\Block\Application\Services\BlockService;

/**
 * Mentions are resolved at compose time by the frontend's Mention picker
 * (a user ID list, never parsed from raw @text) — this service only
 * applies the invariants every caller needs: no duplicate recipients,
 * self-mentions are silently dropped rather than rejected, and mentioning a
 * user with a block relationship (either direction) with the author is
 * silently dropped the same way — no error, the mention just isn't attached.
 */
final class MentionService
{
    public function __construct(
        private readonly BlockService $blocks,
    ) {}

    /**
     * @param  list<int>  $mentionedUserIds
     * @return list<int>
     */
    public function filterRecipients(array $mentionedUserIds, int $authorId): array
    {
        return collect($mentionedUserIds)
            ->map(fn (int|string $id) => (int) $id)
            ->unique()
            ->reject(fn (int $id) => $id === $authorId)
            ->reject(fn (int $id) => $this->blocks->isBlockedEitherWay($authorId, $id))
            ->values()
            ->all();
    }
}
