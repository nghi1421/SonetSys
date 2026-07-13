<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Application;

use App\Core\Tenancy\Domain\Models\Tenant;

final class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function current(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }
}
