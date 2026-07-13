<?php

declare(strict_types=1);

namespace App\Core\Auth;

use App\Core\Auth\Application\Contracts\UserRepositoryInterface;
use App\Core\Auth\Infrastructure\Repositories\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

final class CoreAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
    }
}
