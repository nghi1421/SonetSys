<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Domain\Enums;

enum WalletTransactionType: string
{
    case Credit = 'credit';
    case Debit = 'debit';
}
