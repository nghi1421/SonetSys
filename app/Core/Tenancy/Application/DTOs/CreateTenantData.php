<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Application\DTOs;

final readonly class CreateTenantData
{
    public function __construct(
        public string $name,
        public string $slug,
        public array $enabledModules = [],
        public array $settings = [],
    ) {}
}
