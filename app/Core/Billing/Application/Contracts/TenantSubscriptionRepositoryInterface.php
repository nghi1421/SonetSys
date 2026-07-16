<?php

declare(strict_types=1);

namespace App\Core\Billing\Application\Contracts;

use App\Core\Billing\Domain\Models\TenantSubscription;

interface TenantSubscriptionRepositoryInterface
{
    public function create(array $attributes): TenantSubscription;

    public function findForTenant(int $tenantId): ?TenantSubscription;

    public function update(TenantSubscription $subscription, array $attributes): TenantSubscription;
}
