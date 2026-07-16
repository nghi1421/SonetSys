<?php

declare(strict_types=1);

namespace App\Modules\Menu\Application\DTOs;

final readonly class CreateMenuItemData
{
    public function __construct(
        public string $label,
        public ?int $staticPageId,
    ) {}
}
