<?php

declare(strict_types=1);

namespace App\Modules\Feed\Domain\Enums;

enum PostVisibility: string
{
    case Public = 'public';
    case Members = 'members';
    case Private = 'private';
}
