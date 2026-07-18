<?php

declare(strict_types=1);

namespace App\Modules\Block\Application\Contracts;

use App\Modules\Block\Domain\Models\Block;
use Illuminate\Support\Collection;

interface BlockRepositoryInterface
{
    public function block(int $blockerId, int $blockedId): Block;

    public function unblock(int $blockerId, int $blockedId): void;

    public function isBlocked(int $blockerId, int $blockedId): bool;

    /** @return Collection<int, Block> */
    public function listBlocked(int $blockerId): Collection;
}
