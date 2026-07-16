<?php

declare(strict_types=1);

namespace App\Core\Billing\Http\Controllers;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Billing\Application\Services\PlanService;
use App\Core\Billing\Domain\Models\Plan;
use App\Core\Billing\Http\Requests\CreatePlanRequest;
use App\Core\Billing\Http\Requests\UpdatePlanRequest;
use App\Core\Billing\Http\Resources\PlanResource;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class PlanController extends Controller
{
    public function __construct(
        private readonly PlanService $plans,
    ) {}

    /**
     * Public pricing listing — active plans only, no auth required.
     */
    public function index(): JsonResponse
    {
        return ApiResponse::success(PlanResource::collection($this->plans->listActive()));
    }

    /**
     * Admin listing — includes inactive plans so they can be re-enabled.
     */
    public function adminIndex(): JsonResponse
    {
        Gate::authorize(PermissionSlug::PlansManage->value);

        return ApiResponse::success(PlanResource::collection($this->plans->listAll()));
    }

    public function store(CreatePlanRequest $request): JsonResponse
    {
        Gate::authorize(PermissionSlug::PlansManage->value);

        $plan = $this->plans->create($request->toDto());

        return ApiResponse::success(PlanResource::make($plan), status: 201);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): JsonResponse
    {
        Gate::authorize(PermissionSlug::PlansManage->value);

        $plan = $this->plans->update($plan, $request->toDto());

        return ApiResponse::success(PlanResource::make($plan));
    }

    public function destroy(Plan $plan): JsonResponse
    {
        Gate::authorize(PermissionSlug::PlansManage->value);

        $this->plans->delete($plan);

        return ApiResponse::success();
    }
}
