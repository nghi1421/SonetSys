<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class TenantCreated
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
    ) {}
}
