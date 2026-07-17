<?php

declare(strict_types=1);

namespace App\Modules\Feed\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class PostShared
{
    use Dispatchable;

    public function __construct(
        public readonly int $originalPostId,
        public readonly int $sharePostId,
        public readonly int $sharedByUserId,
        public readonly int $originalAuthorId,
    ) {}
}
