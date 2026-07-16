<?php

declare(strict_types=1);

namespace App\Modules\Menu\Application\DTOs;

final readonly class CreateStaticPageData
{
    public function __construct(
        public string $title,
        public string $content,
        public int $authorId,
    ) {}
}
