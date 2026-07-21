<?php

declare(strict_types=1);

namespace App\Modules\Advertising\Domain\Enums;

enum AdCampaignStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
