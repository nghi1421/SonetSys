<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Application\Services;

use App\Modules\Subscription\Application\Contracts\SubscriptionRepositoryInterface;
use App\Modules\Subscription\Domain\Enums\SubscriptionPlan;
use App\Modules\Subscription\Domain\Enums\SubscriptionStatus;
use App\Modules\Subscription\Domain\Models\UserSubscription;
use App\Modules\Wallet\Application\Services\WalletService;
use App\Modules\Wallet\Domain\Enums\WalletTransactionReason;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SubscriptionService
{
    private const PER_PAGE = 25;

    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptions,
        private readonly WalletService $wallet,
    ) {}

    /**
     * @return array<int, SubscriptionPlan>
     */
    public function plans(): array
    {
        return SubscriptionPlan::cases();
    }

    public function currentFor(int $userId): ?UserSubscription
    {
        return $this->subscriptions->findActiveForUser($userId);
    }

    public function isPremium(int $userId): bool
    {
        $plan = $this->currentFor($userId)?->plan;

        return $plan !== null && $plan !== SubscriptionPlan::Free;
    }

    public function boostFeeWaiverPercentFor(int $userId): int
    {
        return $this->currentFor($userId)?->plan->boostFeeWaiverPercent() ?? 0;
    }

    public function subscribe(int $userId, SubscriptionPlan $plan): UserSubscription
    {
        $current = $this->subscriptions->findActiveForUser($userId);

        if ($current !== null && $current->plan !== SubscriptionPlan::Free) {
            throw ValidationException::withMessages([
                'plan' => 'You already have an active subscription. Cancel it before subscribing to a new plan.',
            ]);
        }

        return DB::transaction(function () use ($userId, $plan, $current): UserSubscription {
            if ($current !== null) {
                $this->expire($current);
            }

            $price = $plan->priceInWalletUnits();

            if ($price > 0) {
                $this->wallet->debit(
                    $userId,
                    $price,
                    WalletTransactionReason::SubscriptionPayment,
                    "Subscription: {$plan->label()} plan",
                    $userId,
                );
            }

            return $this->subscriptions->create([
                'user_id' => $userId,
                'plan' => $plan,
                'status' => SubscriptionStatus::Active,
                'price' => $price,
                'auto_renew' => true,
                'current_period_start' => now(),
                'current_period_end' => $plan === SubscriptionPlan::Free ? now()->addYears(100) : now()->addMonth(),
            ]);
        });
    }

    public function cancel(int $userId, ?int $cancelledBy = null): UserSubscription
    {
        $subscription = $this->subscriptions->findActiveForUser($userId);

        if ($subscription === null) {
            throw ValidationException::withMessages([
                'subscription' => 'No active subscription to cancel.',
            ]);
        }

        if (! $subscription->auto_renew) {
            throw ValidationException::withMessages([
                'subscription' => 'Subscription is already set to end at period end.',
            ]);
        }

        return $this->subscriptions->update($subscription, [
            'auto_renew' => false,
            'cancelled_at' => now(),
            'cancelled_by' => $cancelledBy ?? $userId,
        ]);
    }

    /**
     * @return Collection<int, UserSubscription>
     */
    public function renewDue(): Collection
    {
        $due = $this->subscriptions->dueForRenewal();

        return $due->map(function (UserSubscription $subscription): UserSubscription {
            if (! $subscription->auto_renew) {
                return $this->expire($subscription);
            }

            try {
                return DB::transaction(function () use ($subscription): UserSubscription {
                    $this->wallet->debit(
                        (int) $subscription->user_id,
                        (int) $subscription->price,
                        WalletTransactionReason::SubscriptionPayment,
                        "Subscription renewal: {$subscription->plan->label()} plan",
                        null,
                    );

                    return $this->subscriptions->update($subscription, [
                        'current_period_start' => $subscription->current_period_end,
                        'current_period_end' => $subscription->current_period_end->copy()->addMonth(),
                        'last_payment_failed_at' => null,
                    ]);
                });
            } catch (ValidationException) {
                return $this->expire($subscription, failedPayment: true);
            }
        });
    }

    public function adminList(int $page): LengthAwarePaginator
    {
        return $this->subscriptions->allPaginated($page, self::PER_PAGE);
    }

    public function adminCancel(int $subscriptionId, int $adminId): UserSubscription
    {
        $subscription = $this->subscriptions->findById($subscriptionId);

        if ($subscription === null || $subscription->status !== SubscriptionStatus::Active) {
            throw ValidationException::withMessages([
                'subscription' => 'This subscription is not active.',
            ]);
        }

        return $this->cancel((int) $subscription->user_id, $adminId);
    }

    private function expire(UserSubscription $subscription, bool $failedPayment = false): UserSubscription
    {
        return $this->subscriptions->update($subscription, [
            'status' => SubscriptionStatus::Expired,
            'auto_renew' => false,
            'ended_at' => now(),
            'last_payment_failed_at' => $failedPayment ? now() : $subscription->last_payment_failed_at,
        ]);
    }
}
