<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\DTOs;

final readonly class UpdateCommentData
{
    public function __construct(
        public string $body,
    ) {}
}
