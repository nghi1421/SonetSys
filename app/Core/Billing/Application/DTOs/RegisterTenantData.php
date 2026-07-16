<?php

declare(strict_types=1);

namespace App\Core\Billing\Application\DTOs;

final readonly class RegisterTenantData
{
    public function __construct(
        public string $companyName,
        public string $companySlug,
        public string $adminName,
        public string $adminEmail,
        public string $adminPassword,
        public int $planId,
    ) {}
}
