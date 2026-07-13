<?php

declare(strict_types=1);

namespace App\Modules\Feed\Domain\Enums;

/**
 * MVP scope locks this to a single case (see docs/ARCHITECTURE.md §5) —
 * kept as an enum, not a boolean, so adding reactions later is additive.
 */
enum InteractionType: string
{
    case Like = 'like';
}
