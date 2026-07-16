<?php

declare(strict_types=1);

namespace App\Core\Billing\Http\Controllers;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Billing\Application\Contracts\PlanRepositoryInterface;
use App\Core\Billing\Application\Services\SubscriptionService;
use App\Core\Billing\Http\Requests\UpdateSubscriptionRequest;
use App\Core\Billing\Http\Resources\TenantSubscriptionResource;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
        private readonly PlanRepositoryInterface $plans,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $subscription = $this->subscriptions->currentFor((int) $request->user()->tenant_id);

        return ApiResponse::success(
            $subscription !== null ? TenantSubscriptionResource::make($subscription) : null,
        );
    }

    public function update(UpdateSubscriptionRequest $request): JsonResponse
    {
        Gate::authorize(PermissionSlug::SubscriptionManage->value);

        $plan = $this->plans->findById($request->planId()) ?? throw new ModelNotFoundException;

        $subscription = $this->subscriptions->changePlan((int) $request->user()->tenant_id, $plan);

        return ApiResponse::success(TenantSubscriptionResource::make($subscription->load(['plan', 'tenant'])));
    }
}
