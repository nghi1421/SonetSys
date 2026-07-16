<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\Contracts;

/**
 * Feed-owned contract so Feed's controllers can gate access to group-scoped
 * posts without depending on the Group module directly. Group's ServiceProvider
 * binds the implementation.
 */
interface GroupAccessCheckerInterface
{
    public function canView(int $groupId, int $userId): bool;

    public function canInteract(int $groupId, int $userId): bool;
}
