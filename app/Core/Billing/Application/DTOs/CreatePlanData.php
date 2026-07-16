<?php

declare(strict_types=1);

namespace App\Core\Billing\Application\DTOs;

use App\Core\Billing\Domain\Enums\PlanInterval;

final readonly class CreatePlanData
{
    /**
     * @param  list<string>  $features
     */
    public function __construct(
        public string $name,
        public string $slug,
        public int $priceCents,
        public PlanInterval $interval,
        public ?int $maxUsers = null,
        public array $features = [],
    ) {}
}
