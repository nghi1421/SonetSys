<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Application\Contracts;

use App\Core\Tenancy\Domain\Models\Tenant;

interface TenantRepositoryInterface
{
    public function create(array $attributes): Tenant;

    public function findById(int $id): ?Tenant;

    public function update(Tenant $tenant, array $attributes): Tenant;
}
