<?php

declare(strict_types=1);

namespace App\Modules\Report\Domain\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';
}
