<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Application\Contracts;

use App\Modules\Wallet\Domain\Models\Wallet;
use App\Modules\Wallet\Domain\Models\WalletTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface WalletRepositoryInterface
{
    public function findByUserId(int $userId): ?Wallet;

    public function createForUser(int $userId): Wallet;

    /**
     * Must be called from within a transaction to serialize concurrent
     * credit/debit operations against the same wallet.
     */
    public function lockForUpdate(int $walletId): Wallet;

    public function appendTransaction(int $walletId, array $attributes): WalletTransaction;

    public function updateBalance(int $walletId, int $newBalance): void;

    public function transactionsForUser(int $userId, int $page, int $perPage): LengthAwarePaginator;

    public function allWalletsPaginated(int $page, int $perPage): LengthAwarePaginator;
}
