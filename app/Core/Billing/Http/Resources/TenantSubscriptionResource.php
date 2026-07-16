<?php

declare(strict_types=1);

namespace App\Core\Billing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TenantSubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'starts_at' => $this->starts_at->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'plan' => PlanResource::make($this->whenLoaded('plan')),
        ];
    }
}
