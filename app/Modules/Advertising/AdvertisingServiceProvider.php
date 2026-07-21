<?php

declare(strict_types=1);

namespace App\Modules\Advertising;

use App\Modules\Advertising\Application\Contracts\AdCampaignRepositoryInterface;
use App\Modules\Advertising\Infrastructure\Repositories\EloquentAdCampaignRepository;
use Illuminate\Support\ServiceProvider;

final class AdvertisingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AdCampaignRepositoryInterface::class, EloquentAdCampaignRepository::class);
    }
}
