<?php

declare(strict_types=1);

namespace App\Modules\Feed\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class CommentPosted
{
    use Dispatchable;

    public function __construct(
        public readonly int $commentId,
        public readonly int $postId,
        public readonly int $authorId,
        public readonly ?int $parentCommentAuthorId,
        public readonly int $postAuthorId,
    ) {}
}
