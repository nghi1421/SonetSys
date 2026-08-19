<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Subscription\Application\Services\SubscriptionService;
use App\Modules\Subscription\Domain\Enums\SubscriptionPlan;
use App\Modules\Wallet\Application\Services\WalletService;
use App\Modules\Wallet\Domain\Enums\WalletTransactionReason;
use Illuminate\Support\Collection;

final class DemoFinanceStep
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly SubscriptionService $subscriptions,
    ) {}

    /**
     * @param  Collection<int, User>  $users
     */
    public function run(Collection $users): void
    {
        foreach ($users as $user) {
            $this->wallets->credit(
                $user->id,
                fake()->numberBetween(3000, 9000),
                WalletTransactionReason::AdminTopup,
                'Starter wallet top-up',
            );

            $plan = $this->randomPlan();

            $this->subscriptions->subscribe($user->id, $plan);
        }
    }

    private function randomPlan(): SubscriptionPlan
    {
        return fake()->randomElement([
            ...array_fill(0, 60, SubscriptionPlan::Free),
            ...array_fill(0, 25, SubscriptionPlan::Basic),
            ...array_fill(0, 15, SubscriptionPlan::Pro),
        ]);
    }
}
