<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Http\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Subscription\Application\Services\SubscriptionService;
use App\Modules\Subscription\Http\Requests\SubscribeRequest;
use App\Modules\Subscription\Http\Resources\SubscriptionPlanResource;
use App\Modules\Subscription\Http\Resources\SubscriptionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function plans(): JsonResponse
    {
        return ApiResponse::success(SubscriptionPlanResource::collection($this->subscriptions->plans()));
    }

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscription = $this->subscriptions->currentFor((int) $user->id);

        return ApiResponse::success($subscription !== null ? SubscriptionResource::make($subscription) : null);
    }

    public function store(SubscribeRequest $request): JsonResponse
    {
        $user = $request->user();

        $subscription = $this->subscriptions->subscribe((int) $user->id, $request->plan());

        return ApiResponse::success(SubscriptionResource::make($subscription), status: 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscription = $this->subscriptions->cancel((int) $user->id);

        return ApiResponse::success(SubscriptionResource::make($subscription));
    }
}
