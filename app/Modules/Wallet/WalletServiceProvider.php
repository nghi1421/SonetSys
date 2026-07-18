<?php

declare(strict_types=1);

namespace App\Modules\Wallet;

use App\Modules\Wallet\Application\Contracts\WalletRepositoryInterface;
use App\Modules\Wallet\Infrastructure\Repositories\EloquentWalletRepository;
use Illuminate\Support\ServiceProvider;

final class WalletServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WalletRepositoryInterface::class, EloquentWalletRepository::class);
    }
}
