<?php

declare(strict_types=1);

namespace App\Modules\Feed\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class UserMentioned
{
    use Dispatchable;

    /**
     * @param  list<int>  $mentionedUserIds
     */
    public function __construct(
        public readonly string $mentionableType,
        public readonly int $mentionableId,
        public readonly int $mentionedByUserId,
        public readonly array $mentionedUserIds,
    ) {}
}
