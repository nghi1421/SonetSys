<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Application\Contracts;

use App\Core\Tenancy\Domain\Models\Tenant;

interface TenantRepositoryInterface
{
    public function create(array $attributes): Tenant;
}
