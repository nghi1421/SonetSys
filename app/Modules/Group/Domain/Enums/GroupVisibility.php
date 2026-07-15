<?php

declare(strict_types=1);

namespace App\Modules\Group\Domain\Enums;

enum GroupVisibility: string
{
    case Public = 'public';
    case Private = 'private';
}
