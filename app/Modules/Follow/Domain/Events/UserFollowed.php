<?php

declare(strict_types=1);

namespace App\Modules\Follow\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class UserFollowed
{
    use Dispatchable;

    public function __construct(
        public readonly int $followerId,
        public readonly int $followedId,
    ) {}
}
