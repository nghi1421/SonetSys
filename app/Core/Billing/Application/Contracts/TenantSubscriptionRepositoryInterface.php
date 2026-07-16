<?php

declare(strict_types=1);

namespace App\Core\Billing\Application\Contracts;

use App\Core\Billing\Domain\Models\TenantSubscription;
use Illuminate\Support\Collection;

interface TenantSubscriptionRepositoryInterface
{
    public function create(array $attributes): TenantSubscription;

    public function findForTenant(int $tenantId): ?TenantSubscription;

    /**
     * @param  array<int, int>  $tenantIds
     * @return Collection<int, TenantSubscription> keyed by tenant_id
     */
    public function findManyForTenants(array $tenantIds): Collection;

    public function update(TenantSubscription $subscription, array $attributes): TenantSubscription;
}
