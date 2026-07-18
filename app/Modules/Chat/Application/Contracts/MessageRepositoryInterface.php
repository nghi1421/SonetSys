<?php

declare(strict_types=1);

namespace App\Modules\Chat\Application\Contracts;

use App\Modules\Chat\Domain\Models\Message;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface MessageRepositoryInterface
{
    public function create(int $conversationId, int $senderId, string $body): Message;

    /**
     * Mirrors EloquentPostRepository::cursorPaginate's tie-break-by-id
     * logic: returns messages ordered newest-first (created_at desc, id
     * desc), each successive page strictly older than the given cursor.
     *
     * @return Collection<int, Message>
     */
    public function cursorPaginate(
        int $conversationId,
        ?Carbon $afterCreatedAt,
        ?int $afterId,
        int $limit,
    ): Collection;
}
