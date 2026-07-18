<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Domain\Models;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Wallet\Domain\Enums\WalletTransactionReason;
use App\Modules\Wallet\Domain\Enums\WalletTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'balance_after',
        'reason',
        'description',
        'reference_type',
        'reference_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'balance_after' => 'integer',
            'type' => WalletTransactionType::class,
            'reason' => WalletTransactionReason::class,
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
