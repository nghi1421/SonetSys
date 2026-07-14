<?php

declare(strict_types=1);

namespace App\Modules\Feed\Domain\Enums;

enum MediaType: string
{
    case Image = 'image';
    case Video = 'video';
    case Sticker = 'sticker';
}
