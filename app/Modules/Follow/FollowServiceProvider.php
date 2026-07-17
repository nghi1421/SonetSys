<?php

declare(strict_types=1);

namespace App\Modules\Follow;

use App\Modules\Follow\Application\Contracts\FollowRepositoryInterface;
use App\Modules\Follow\Infrastructure\Repositories\EloquentFollowRepository;
use Illuminate\Support\ServiceProvider;

final class FollowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FollowRepositoryInterface::class, EloquentFollowRepository::class);
    }
}
