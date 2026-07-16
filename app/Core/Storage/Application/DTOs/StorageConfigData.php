<?php

declare(strict_types=1);

namespace App\Core\Storage\Application\DTOs;

use App\Core\Storage\Domain\Enums\StorageDriver;

final readonly class StorageConfigData
{
    public function __construct(
        public StorageDriver $driver,
        public ?string $bucket = null,
        public ?string $region = null,
        public ?string $key = null,
        public ?string $secret = null,
        public ?string $endpoint = null,
        public bool $usePathStyleEndpoint = false,
    ) {}
}
