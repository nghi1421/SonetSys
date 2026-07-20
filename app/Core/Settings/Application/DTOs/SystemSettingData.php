<?php

declare(strict_types=1);

namespace App\Core\Settings\Application\DTOs;

use App\Core\Settings\Domain\Enums\CacheDriver;
use App\Core\Settings\Domain\Enums\RedisClient;

final readonly class SystemSettingData
{
    public function __construct(
        public CacheDriver $cacheDriver,
        public RedisClient $redisClient,
        public string $redisHost,
        public int $redisPort,
        public ?string $redisPassword,
        public int $redisDatabase,
        public string $mailHost,
        public int $mailPort,
        public ?string $mailUsername,
        public ?string $mailPassword,
        public ?string $mailEncryption,
        public string $mailFromAddress,
        public string $mailFromName,
        public int $maxUploadSizeKb,
    ) {}
}
