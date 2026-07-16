<?php

declare(strict_types=1);

namespace App\Core\Billing\Domain\Enums;

enum PlanInterval: string
{
    case Free = 'free';
    case Monthly = 'month';
    case Yearly = 'year';
}
