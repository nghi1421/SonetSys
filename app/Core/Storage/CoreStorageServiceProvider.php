<?php

declare(strict_types=1);

namespace App\Core\Storage;

use App\Core\Storage\Application\Contracts\MediaRepositoryInterface;
use App\Core\Storage\Infrastructure\Repositories\EloquentMediaRepository;
use Illuminate\Support\ServiceProvider;

final class CoreStorageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MediaRepositoryInterface::class, EloquentMediaRepository::class);
    }
}
