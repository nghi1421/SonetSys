<?php

declare(strict_types=1);

namespace App\Core\Billing;

use App\Core\Billing\Application\Contracts\PlanRepositoryInterface;
use App\Core\Billing\Application\Contracts\TenantSubscriptionRepositoryInterface;
use App\Core\Billing\Infrastructure\Repositories\EloquentPlanRepository;
use App\Core\Billing\Infrastructure\Repositories\EloquentTenantSubscriptionRepository;
use Illuminate\Support\ServiceProvider;

final class CoreBillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PlanRepositoryInterface::class, EloquentPlanRepository::class);
        $this->app->bind(TenantSubscriptionRepositoryInterface::class, EloquentTenantSubscriptionRepository::class);
    }
}
