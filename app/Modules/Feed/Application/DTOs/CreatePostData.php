<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\DTOs;

use App\Modules\Feed\Domain\Enums\PostVisibility;

final readonly class CreatePostData
{
    public function __construct(
        public string $body,
        public PostVisibility $visibility,
        public int $tenantId,
        public int $authorId,
        public array $metadata = [],
    ) {}
}
