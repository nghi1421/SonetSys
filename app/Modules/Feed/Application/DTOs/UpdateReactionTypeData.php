<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\DTOs;

use Illuminate\Http\UploadedFile;

final readonly class UpdateReactionTypeData
{
    public function __construct(
        public string $label,
        public ?string $emoji = null,
        public ?UploadedFile $icon = null,
        public int $sortOrder = 0,
    ) {}
}
