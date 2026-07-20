<?php

declare(strict_types=1);

namespace App\Core\Settings\Domain\Enums;

enum CacheDriver: string
{
    case Database = 'database';
    case Redis = 'redis';
}
