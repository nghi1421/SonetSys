<?php

declare(strict_types=1);

namespace App\Modules\Block\Infrastructure\Repositories;

use App\Modules\Block\Application\Contracts\BlockRepositoryInterface;
use App\Modules\Block\Domain\Models\Block;
use Illuminate\Support\Collection;

final class EloquentBlockRepository implements BlockRepositoryInterface
{
    public function block(int $blockerId, int $blockedId): Block
    {
        return Block::query()->firstOrCreate([
            'blocker_id' => $blockerId,
            'blocked_id' => $blockedId,
        ]);
    }

    public function unblock(int $blockerId, int $blockedId): void
    {
        Block::query()
            ->where('blocker_id', $blockerId)
            ->where('blocked_id', $blockedId)
            ->delete();
    }

    public function isBlocked(int $blockerId, int $blockedId): bool
    {
        return Block::query()
            ->where('blocker_id', $blockerId)
            ->where('blocked_id', $blockedId)
            ->exists();
    }

    public function listBlocked(int $blockerId): Collection
    {
        return Block::query()
            ->where('blocker_id', $blockerId)
            ->with('blocked')
            ->orderByDesc('created_at')
            ->get();
    }
}
