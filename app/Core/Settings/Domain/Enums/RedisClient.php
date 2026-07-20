<?php

declare(strict_types=1);

namespace App\Core\Settings\Domain\Enums;

enum RedisClient: string
{
    case Predis = 'predis';
    case PhpRedis = 'phpredis';
}
