<?php

declare(strict_types=1);

namespace App\Modules\Feed\Domain\Events;

use App\Modules\Feed\Domain\Enums\InteractionType;
use Illuminate\Foundation\Events\Dispatchable;

final class ContentLiked
{
    use Dispatchable;

    public function __construct(
        public readonly string $interactableType,
        public readonly int $interactableId,
        public readonly int $likedByUserId,
        public readonly int $contentAuthorId,
        public readonly InteractionType $reactionType,
        public readonly int $postId,
    ) {}
}
