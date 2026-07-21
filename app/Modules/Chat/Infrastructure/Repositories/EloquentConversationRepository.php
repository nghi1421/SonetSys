<?php

declare(strict_types=1);

namespace App\Modules\Chat\Infrastructure\Repositories;

use App\Modules\Chat\Application\Contracts\ConversationRepositoryInterface;
use App\Modules\Chat\Domain\Models\Conversation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class EloquentConversationRepository implements ConversationRepositoryInterface
{
    public function findById(int $id): ?Conversation
    {
        return Conversation::query()->find($id);
    }

    public function findBetween(int $a, int $b): ?Conversation
    {
        [$userOneId, $userTwoId] = $this->normalize($a, $b);

        return Conversation::query()
            ->where('user_one_id', $userOneId)
            ->where('user_two_id', $userTwoId)
            ->first();
    }

    public function createBetween(int $a, int $b): Conversation
    {
        [$userOneId, $userTwoId] = $this->normalize($a, $b);

        return Conversation::query()->firstOrCreate([
            'user_one_id' => $userOneId,
            'user_two_id' => $userTwoId,
        ]);
    }

    public function listForUser(int $userId): Collection
    {
        return Conversation::query()
            ->where(function ($query) use ($userId): void {
                $query->where('user_one_id', $userId)->orWhere('user_two_id', $userId);
            })
            ->with(['userOne', 'userTwo'])
            ->orderByRaw('last_message_at IS NULL')
            ->orderByDesc('last_message_at')
            ->get();
    }

    public function touchLastMessageAt(Conversation $conversation, Carbon $at): void
    {
        $conversation->forceFill(['last_message_at' => $at])->save();
    }

    public function markRead(Conversation $conversation, int $viewerId): void
    {
        $column = $conversation->user_one_id === $viewerId
            ? 'user_one_last_read_at'
            : 'user_two_last_read_at';

        $conversation->forceFill([$column => now()])->save();
    }

    public function unreadCountFor(int $userId): int
    {
        return Conversation::query()
            ->whereNotNull('last_message_at')
            ->where(function ($query) use ($userId): void {
                $query->where(function ($inner) use ($userId): void {
                    $inner->where('user_one_id', $userId)
                        ->where(function ($read): void {
                            $read->whereNull('user_one_last_read_at')
                                ->orWhereColumn('last_message_at', '>', 'user_one_last_read_at');
                        });
                })->orWhere(function ($inner) use ($userId): void {
                    $inner->where('user_two_id', $userId)
                        ->where(function ($read): void {
                            $read->whereNull('user_two_last_read_at')
                                ->orWhereColumn('last_message_at', '>', 'user_two_last_read_at');
                        });
                });
            })
            ->count();
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function normalize(int $a, int $b): array
    {
        return $a < $b ? [$a, $b] : [$b, $a];
    }
}
