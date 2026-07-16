<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Infrastructure\Repositories;

use App\Core\Tenancy\Application\Contracts\TenantRepositoryInterface;
use App\Core\Tenancy\Domain\Models\Tenant;
use Illuminate\Support\Collection;

final class EloquentTenantRepository implements TenantRepositoryInterface
{
    public function create(array $attributes): Tenant
    {
        return Tenant::query()->create($attributes);
    }

    public function findById(int $id): ?Tenant
    {
        return Tenant::query()->find($id);
    }

    public function update(Tenant $tenant, array $attributes): Tenant
    {
        $tenant->fill($attributes)->save();

        return $tenant;
    }

    public function listAll(): Collection
    {
        return Tenant::query()->orderByDesc('created_at')->get();
    }
}
