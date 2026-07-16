<?php

declare(strict_types=1);

namespace App\Core\Storage\Domain\Enums;

enum StorageDriver: string
{
    case Local = 'local';
    case S3 = 's3';
}
