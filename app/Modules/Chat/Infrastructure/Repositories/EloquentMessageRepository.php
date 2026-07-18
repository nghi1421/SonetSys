<?php

declare(strict_types=1);

namespace App\Modules\Chat\Infrastructure\Repositories;

use App\Modules\Chat\Application\Contracts\MessageRepositoryInterface;
use App\Modules\Chat\Domain\Models\Message;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class EloquentMessageRepository implements MessageRepositoryInterface
{
    public function create(int $conversationId, int $senderId, string $body): Message
    {
        return Message::query()->create([
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'body' => $body,
        ]);
    }

    public function cursorPaginate(
        int $conversationId,
        ?Carbon $afterCreatedAt,
        ?int $afterId,
        int $limit,
    ): Collection {
        return Message::query()
            ->where('conversation_id', $conversationId)
            ->when(
                $afterCreatedAt !== null && $afterId !== null,
                fn ($query) => $query->where(function ($inner) use ($afterCreatedAt, $afterId): void {
                    $inner->where('created_at', '<', $afterCreatedAt)
                        ->orWhere(function ($tie) use ($afterCreatedAt, $afterId): void {
                            $tie->where('created_at', $afterCreatedAt)->where('id', '<', $afterId);
                        });
                }),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->with('sender')
            ->get();
    }
}
