<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Subscription\Application\Services\SubscriptionService;
use App\Modules\Subscription\Domain\Enums\SubscriptionStatus;
use Illuminate\Console\Command;

final class RenewSubscriptions extends Command
{
    protected $signature = 'subscriptions:renew';

    protected $description = 'Renew due subscriptions with auto-renew on, or expire them on failed payment / cancellation.';

    public function handle(SubscriptionService $subscriptions): int
    {
        $processed = $subscriptions->renewDue();
        $renewed = $processed->where('status', SubscriptionStatus::Active)->count();
        $expired = $processed->where('status', SubscriptionStatus::Expired)->count();

        $this->info(sprintf('Renewed %d, expired %d subscription(s).', $renewed, $expired));

        return self::SUCCESS;
    }
}
