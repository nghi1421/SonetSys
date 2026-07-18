<?php

declare(strict_types=1);

namespace App\Modules\Block;

use App\Modules\Block\Application\Contracts\BlockRepositoryInterface;
use App\Modules\Block\Infrastructure\Repositories\EloquentBlockRepository;
use Illuminate\Support\ServiceProvider;

final class BlockServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BlockRepositoryInterface::class, EloquentBlockRepository::class);
    }
}
