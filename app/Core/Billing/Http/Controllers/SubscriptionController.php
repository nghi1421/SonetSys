<?php

declare(strict_types=1);

namespace App\Core\Billing\Http\Controllers;

use App\Core\Billing\Application\Services\SubscriptionService;
use App\Core\Billing\Http\Resources\TenantSubscriptionResource;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $subscription = $this->subscriptions->currentFor((int) $request->user()->tenant_id);

        return ApiResponse::success(
            $subscription !== null ? TenantSubscriptionResource::make($subscription) : null,
        );
    }
}
