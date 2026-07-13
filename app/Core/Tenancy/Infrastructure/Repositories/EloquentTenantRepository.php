<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Infrastructure\Repositories;

use App\Core\Tenancy\Application\Contracts\TenantRepositoryInterface;
use App\Core\Tenancy\Domain\Models\Tenant;

final class EloquentTenantRepository implements TenantRepositoryInterface
{
    public function create(array $attributes): Tenant
    {
        return Tenant::query()->create($attributes);
    }
}
