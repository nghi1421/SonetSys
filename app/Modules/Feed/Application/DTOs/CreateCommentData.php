<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\DTOs;

final readonly class CreateCommentData
{
    public function __construct(
        public string $body,
        public int $postId,
        public ?int $parentId,
        public int $tenantId,
        public int $authorId,
    ) {}
}
