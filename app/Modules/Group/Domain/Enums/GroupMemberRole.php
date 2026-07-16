<?php

declare(strict_types=1);

namespace App\Modules\Group\Domain\Enums;

enum GroupMemberRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
}
