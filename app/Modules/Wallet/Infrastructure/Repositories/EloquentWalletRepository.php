<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Infrastructure\Repositories;

use App\Modules\Wallet\Application\Contracts\WalletRepositoryInterface;
use App\Modules\Wallet\Domain\Models\Wallet;
use App\Modules\Wallet\Domain\Models\WalletTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentWalletRepository implements WalletRepositoryInterface
{
    public function findByUserId(int $userId): ?Wallet
    {
        return Wallet::query()->where('user_id', $userId)->first();
    }

    public function createForUser(int $userId): Wallet
    {
        return Wallet::query()->create([
            'user_id' => $userId,
            'balance' => 0,
        ]);
    }

    public function lockForUpdate(int $walletId): Wallet
    {
        return Wallet::query()->lockForUpdate()->findOrFail($walletId);
    }

    public function appendTransaction(int $walletId, array $attributes): WalletTransaction
    {
        return WalletTransaction::query()->create([
            'wallet_id' => $walletId,
            ...$attributes,
        ]);
    }

    public function updateBalance(int $walletId, int $newBalance): void
    {
        Wallet::query()->where('id', $walletId)->update(['balance' => $newBalance]);
    }

    public function transactionsForUser(int $userId, int $page, int $perPage): LengthAwarePaginator
    {
        return WalletTransaction::query()
            ->join('wallets', 'wallets.id', '=', 'wallet_transactions.wallet_id')
            ->where('wallets.user_id', $userId)
            ->select('wallet_transactions.*')
            ->orderByDesc('wallet_transactions.created_at')
            ->paginate($perPage, page: $page);
    }

    public function allWalletsPaginated(int $page, int $perPage): LengthAwarePaginator
    {
        return Wallet::query()
            ->with('user')
            ->orderByDesc('id')
            ->paginate($perPage, page: $page);
    }
}
