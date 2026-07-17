<?php

declare(strict_types=1);

namespace App\Modules\Group\Application\DTOs;

use App\Modules\Group\Domain\Enums\GroupVisibility;

final readonly class CreateGroupData
{
    public function __construct(
        public string $name,
        public ?string $description,
        public GroupVisibility $visibility,
        public int $ownerId,
    ) {}
}
