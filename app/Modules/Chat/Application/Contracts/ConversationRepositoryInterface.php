<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Contracts;

use App\Modules\Chat\Domain\Models\Conversation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface ConversationRepositoryInterface
{
    public function findById(int $id): ?Conversation;

    /**
     * Normalizes the pair order internally before querying.
     */
    public function findBetween(int $a, int $b): ?Conversation;

    /**
     * Normalizes the pair order internally before creating, so a
     * conversation between two users is never duplicated in either order.
     */
    public function createBetween(int $a, int $b): Conversation;

    /**
     * Ordered by last_message_at desc, nulls last.
     *
     * @return Collection<int, Conversation>
     */
    public function listForUser(int $userId): Collection;

    public function touchLastMessageAt(Conversation $conversation, Carbon $at): void;

    /**
     * Updates whichever of user_one_last_read_at/user_two_last_read_at
     * matches the viewer.
     */
    public function markRead(Conversation $conversation, int $viewerId): void;

    public function unreadCountFor(int $userId): int;
}
