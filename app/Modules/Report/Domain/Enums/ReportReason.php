<?php

declare(strict_types=1);

namespace App\Modules\Report\Domain\Enums;

enum ReportReason: string
{
    case Spam = 'spam';
    case Harassment = 'harassment';
    case Inappropriate = 'inappropriate';
    case Other = 'other';
}
