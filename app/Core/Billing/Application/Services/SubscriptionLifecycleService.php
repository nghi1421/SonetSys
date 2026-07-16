<?php

declare(strict_types=1);

namespace App\Core\Billing\Application\Services;

use App\Core\Billing\Domain\Enums\SubscriptionStatus;
use App\Core\Billing\Domain\Models\TenantSubscription;
use App\Core\Tenancy\Application\Contracts\TenantRepositoryInterface;
use App\Core\Tenancy\Domain\Enums\TenantStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Enforces the trial-expiry side of licensing: a Trialing subscription past
 * its expires_at is transitioned to Expired and its tenant is Suspended.
 * Only ever touches Trialing subscriptions — an Active (free-plan or
 * manually-reactivated) subscription never expires here, since there's no
 * real payment gateway to fail a renewal against.
 */
final class SubscriptionLifecycleService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
    ) {}

    public function expireOverdueTrials(): int
    {
        $overdue = TenantSubscription::query()
            ->where('status', SubscriptionStatus::Trialing)
            ->where('expires_at', '<=', now())
            ->with('tenant')
            ->get();

        foreach ($overdue as $subscription) {
            DB::transaction(function () use ($subscription): void {
                $subscription->fill(['status' => SubscriptionStatus::Expired])->save();

                if ($subscription->tenant !== null) {
                    $this->tenants->update($subscription->tenant, ['status' => TenantStatus::Suspended]);
                }
            });

            Log::info('Tenant subscription trial expired; tenant suspended.', [
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
            ]);
        }

        return $overdue->count();
    }
}
