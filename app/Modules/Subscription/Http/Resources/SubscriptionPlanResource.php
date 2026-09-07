<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Http\Resources;

use App\Modules\Subscription\Domain\Enums\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SubscriptionPlanResource extends JsonResource
{
    public function __construct(private readonly SubscriptionPlan $plan)
    {
        parent::__construct($plan);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->plan->value,
            'label' => $this->plan->label(),
            'price' => $this->plan->priceInWalletUnits(),
            'boost_fee_waiver_percent' => $this->plan->boostFeeWaiverPercent(),
        ];
    }
}
