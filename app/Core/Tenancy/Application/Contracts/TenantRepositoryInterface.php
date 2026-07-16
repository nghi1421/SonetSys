<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Application\Contracts;

use App\Core\Tenancy\Domain\Models\Tenant;
use Illuminate\Support\Collection;

interface TenantRepositoryInterface
{
    public function create(array $attributes): Tenant;

    public function findById(int $id): ?Tenant;

    public function update(Tenant $tenant, array $attributes): Tenant;

    /**
     * @return Collection<int, Tenant>
     */
    public function listAll(): Collection;
}
