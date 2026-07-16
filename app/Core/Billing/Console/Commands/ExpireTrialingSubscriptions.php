<?php

declare(strict_types=1);

namespace App\Core\Billing\Console\Commands;

use App\Core\Billing\Application\Services\SubscriptionLifecycleService;
use Illuminate\Console\Command;

final class ExpireTrialingSubscriptions extends Command
{
    protected $signature = 'billing:expire-trials';

    protected $description = 'Expire trialing subscriptions past their expiry date and suspend their tenant.';

    public function handle(SubscriptionLifecycleService $lifecycle): int
    {
        $count = $lifecycle->expireOverdueTrials();

        $this->info("Expired {$count} trialing subscription(s).");

        return self::SUCCESS;
    }
}
