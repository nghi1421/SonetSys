<?php

declare(strict_types=1);

namespace App\Core\Tenancy;

use App\Core\Tenancy\Application\Contracts\TenantRepositoryInterface;
use App\Core\Tenancy\Application\TenantContext;
use App\Core\Tenancy\Infrastructure\Repositories\EloquentTenantRepository;
use Illuminate\Support\ServiceProvider;

final class CoreTenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->bind(TenantRepositoryInterface::class, EloquentTenantRepository::class);
    }
}
