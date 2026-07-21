<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Application\Services;

use App\Modules\Wallet\Application\Contracts\WalletRepositoryInterface;
use App\Modules\Wallet\Domain\Enums\WalletTransactionReason;
use App\Modules\Wallet\Domain\Enums\WalletTransactionType;
use App\Modules\Wallet\Domain\Models\WalletTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class WalletService
{
    private const PER_PAGE = 25;

    public function __construct(
        private readonly WalletRepositoryInterface $wallets,
    ) {}

    public function balanceFor(int $userId): int
    {
        $wallet = $this->wallets->findByUserId($userId) ?? $this->wallets->createForUser($userId);

        return $wallet->balance;
    }

    public function credit(
        int $userId,
        int $amount,
        WalletTransactionReason $reason,
        ?string $description = null,
        ?int $createdBy = null,
    ): WalletTransaction {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($userId, $amount, $reason, $description, $createdBy): WalletTransaction {
            $wallet = $this->wallets->findByUserId($userId) ?? $this->wallets->createForUser($userId);
            $wallet = $this->wallets->lockForUpdate($wallet->id);

            $newBalance = $wallet->balance + $amount;
            $this->wallets->updateBalance($wallet->id, $newBalance);

            return $this->wallets->appendTransaction($wallet->id, [
                'type' => WalletTransactionType::Credit,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reason' => $reason,
                'description' => $description,
                'created_by' => $createdBy,
            ]);
        });
    }

    public function debit(
        int $userId,
        int $amount,
        WalletTransactionReason $reason,
        ?string $description = null,
        ?int $createdBy = null,
    ): WalletTransaction {
        $this->assertPositiveAmount($amount);

        return DB::transaction(function () use ($userId, $amount, $reason, $description, $createdBy): WalletTransaction {
            $wallet = $this->wallets->findByUserId($userId) ?? $this->wallets->createForUser($userId);
            $wallet = $this->wallets->lockForUpdate($wallet->id);

            if ($amount > $wallet->balance) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient wallet balance.',
                ]);
            }

            $newBalance = $wallet->balance - $amount;
            $this->wallets->updateBalance($wallet->id, $newBalance);

            return $this->wallets->appendTransaction($wallet->id, [
                'type' => WalletTransactionType::Debit,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reason' => $reason,
                'description' => $description,
                'created_by' => $createdBy,
            ]);
        });
    }

    public function transactionsForUser(int $userId, int $page): LengthAwarePaginator
    {
        return $this->wallets->transactionsForUser($userId, $page, self::PER_PAGE);
    }

    public function allWallets(int $page): LengthAwarePaginator
    {
        return $this->wallets->allWalletsPaginated($page, self::PER_PAGE);
    }

    private function assertPositiveAmount(int $amount): void
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Amount must be greater than zero.',
            ]);
        }
    }
}
