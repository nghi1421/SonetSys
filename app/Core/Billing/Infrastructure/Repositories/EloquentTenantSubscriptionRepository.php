<?php

declare(strict_types=1);

namespace App\Core\Billing\Infrastructure\Repositories;

use App\Core\Billing\Application\Contracts\TenantSubscriptionRepositoryInterface;
use App\Core\Billing\Domain\Models\TenantSubscription;

final class EloquentTenantSubscriptionRepository implements TenantSubscriptionRepositoryInterface
{
    public function create(array $attributes): TenantSubscription
    {
        return TenantSubscription::query()->create($attributes);
    }

    public function findForTenant(int $tenantId): ?TenantSubscription
    {
        return TenantSubscription::query()->where('tenant_id', $tenantId)->with('plan')->first();
    }

    public function update(TenantSubscription $subscription, array $attributes): TenantSubscription
    {
        $subscription->fill($attributes)->save();

        return $subscription;
    }
}
