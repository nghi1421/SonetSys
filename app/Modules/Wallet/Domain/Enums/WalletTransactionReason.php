<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Domain\Enums;

enum WalletTransactionReason: string
{
    case AdminTopup = 'admin_topup';
    case AdSpend = 'ad_spend';
    case AdRefund = 'ad_refund';
}
