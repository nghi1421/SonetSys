<?php

declare(strict_types=1);

namespace App\Core\Billing\Application\Services;

use App\Core\Billing\Application\Contracts\TenantSubscriptionRepositoryInterface;
use App\Core\Billing\Domain\Enums\SubscriptionStatus;
use App\Core\Billing\Domain\Models\Plan;
use App\Core\Billing\Domain\Models\TenantSubscription;
use App\Core\Tenancy\Application\Contracts\TenantRepositoryInterface;
use App\Core\Tenancy\Domain\Enums\TenantStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        private readonly TenantRepositoryInterface $tenants,
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

    /**
     * @param  array<int, int>  $tenantIds
     * @return Collection<int, TenantSubscription> keyed by tenant_id
     */
    public function manyFor(array $tenantIds): Collection
    {
        return $this->subscriptions->findManyForTenants($tenantIds);
    }

    /**
     * Self-service plan change. Free plan reactivates the tenant immediately
     * (the suspended-tenant self-recovery path); a paid plan re-enters a
     * fresh trial, same as a brand-new signup.
     *
     * A suspended tenant may only self-recover via the Free plan — picking a
     * paid plan instead would let it loop trial resets forever with no
     * payment and no super-admin involvement, defeating suspension entirely.
     * Only a super admin (via TenantService::reactivate()) can put a
     * suspended tenant back onto a paid trial.
     */
    public function changePlan(int $tenantId, Plan $newPlan): TenantSubscription
    {
        return DB::transaction(function () use ($tenantId, $newPlan): TenantSubscription {
            $subscription = $this->subscriptions->findForTenant($tenantId)
                ?? throw new \RuntimeException("No subscription found for tenant {$tenantId}.");

            $isFree = $newPlan->price_cents === 0;

            $tenant = $this->tenants->findById($tenantId);

            if ($tenant !== null && $tenant->status === TenantStatus::Suspended && ! $isFree) {
                throw ValidationException::withMessages([
                    'plan_id' => ['A suspended workspace can only switch to the Free plan to reactivate.'],
                ]);
            }

            $subscription = $this->subscriptions->update($subscription, [
                'plan_id' => $newPlan->id,
                'status' => $isFree ? SubscriptionStatus::Active : SubscriptionStatus::Trialing,
                'starts_at' => now(),
                'expires_at' => $isFree ? null : now()->addDays(self::TRIAL_DAYS),
            ]);

            if ($tenant !== null) {
                $this->tenants->update($tenant, [
                    'status' => $isFree ? TenantStatus::Active : TenantStatus::Trial,
                ]);
            }

            return $subscription;
        });
    }
}
