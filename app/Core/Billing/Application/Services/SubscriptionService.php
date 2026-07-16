<?php

declare(strict_types=1);

namespace App\Core\Billing\Application\Services;

use App\Core\Billing\Application\Contracts\TenantSubscriptionRepositoryInterface;
use App\Core\Billing\Domain\Enums\SubscriptionStatus;
use App\Core\Billing\Domain\Models\Plan;
use App\Core\Billing\Domain\Models\TenantSubscription;

final class SubscriptionService
{
    /**
     * No real payment gateway is wired up yet — picking a paid plan starts a
     * time-limited trial instead of an actual charge. A human (super admin)
     * decides what happens once it expires; nothing here auto-suspends it.
     */
    private const TRIAL_DAYS = 14;

    public function __construct(
        private readonly TenantSubscriptionRepositoryInterface $subscriptions,
    ) {}

    public function createForNewTenant(int $tenantId, Plan $plan): TenantSubscription
    {
        $isFree = $plan->price_cents === 0;

        return $this->subscriptions->create([
            'tenant_id' => $tenantId,
            'plan_id' => $plan->id,
            'status' => $isFree ? SubscriptionStatus::Active : SubscriptionStatus::Trialing,
            'starts_at' => now(),
            'expires_at' => $isFree ? null : now()->addDays(self::TRIAL_DAYS),
        ]);
    }

    public function currentFor(int $tenantId): ?TenantSubscription
    {
        return $this->subscriptions->findForTenant($tenantId);
    }
}
