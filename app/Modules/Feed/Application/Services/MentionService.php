<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\Services;

/**
 * Mentions are resolved at compose time by the frontend's Mention picker
 * (a user ID list, never parsed from raw @text) — this service only
 * applies the two invariants every caller needs: no duplicate recipients,
 * and self-mentions are silently dropped rather than rejected.
 */
final class MentionService
{
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
            ->values()
            ->all();
    }
}
