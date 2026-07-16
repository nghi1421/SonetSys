<?php

declare(strict_types=1);

namespace App\Core\Billing\Application\DTOs;

use App\Core\Billing\Domain\Enums\PlanInterval;

final readonly class UpdatePlanData
{
    /**
     * @param  list<string>  $features
     */
    public function __construct(
        public string $name,
        public int $priceCents,
        public PlanInterval $interval,
        public ?int $maxUsers,
        public array $features,
        public bool $isActive,
    ) {}
}
