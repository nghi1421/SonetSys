<?php

declare(strict_types=1);

namespace App\Modules\Group\Domain\Enums;

enum GroupMemberStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
}
