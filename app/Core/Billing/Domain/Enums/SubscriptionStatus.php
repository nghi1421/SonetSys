<?php

declare(strict_types=1);

namespace App\Core\Billing\Domain\Enums;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case Expired = 'expired';
    case Canceled = 'canceled';
}
