<?php

declare(strict_types=1);

namespace App\Modules\Subscription;

use App\Modules\Subscription\Application\Contracts\SubscriptionRepositoryInterface;
use App\Modules\Subscription\Infrastructure\Repositories\EloquentSubscriptionRepository;
use Illuminate\Support\ServiceProvider;

final class SubscriptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SubscriptionRepositoryInterface::class, EloquentSubscriptionRepository::class);
    }
}
