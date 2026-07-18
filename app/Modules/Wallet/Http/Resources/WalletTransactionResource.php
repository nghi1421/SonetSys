<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'amount' => $this->amount,
            'balance_after' => $this->balance_after,
            'reason' => $this->reason->value,
            'description' => $this->description,
            'created_at' => $this->created_at,
        ];
    }
}
