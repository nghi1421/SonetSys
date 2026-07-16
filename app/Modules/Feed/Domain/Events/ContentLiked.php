<?php

declare(strict_types=1);

namespace App\Modules\Feed\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class ContentLiked
{
    use Dispatchable;

    public function __construct(
        public readonly string $interactableType,
        public readonly int $interactableId,
        public readonly int $likedByUserId,
        public readonly int $contentAuthorId,
    ) {}
}
