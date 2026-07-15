<?php

declare(strict_types=1);

namespace App\Modules\Menu\Application\DTOs;

final readonly class UpdateStaticPageData
{
    public function __construct(
        public string $title,
        public string $content,
    ) {}
}
