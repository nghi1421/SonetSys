<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Domain\Enums;

enum SubscriptionPlan: string
{
    case Basic = 'basic';
    case Pro = 'pro';

    public function label(): string
    {
        return match ($this) {
            self::Basic => 'Basic',
            self::Pro => 'Pro',
        };
    }

    public function priceInWalletUnits(): int
    {
        return match ($this) {
            self::Basic => 1000,
            self::Pro => 2500,
        };
    }

    public function boostFeeWaiverPercent(): int
    {
        return match ($this) {
            self::Basic => 50,
            self::Pro => 100,
        };
    }
}
