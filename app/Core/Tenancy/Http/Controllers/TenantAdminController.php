<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Http\Controllers;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Billing\Application\Services\SubscriptionService;
use App\Core\Billing\Http\Resources\TenantSubscriptionResource;
use App\Core\Support\ApiResponse;
use App\Core\Tenancy\Application\Services\TenantService;
use App\Core\Tenancy\Domain\Models\Tenant;
use App\Core\Tenancy\Http\Resources\TenantResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Composes Tenancy + Billing here at the Http layer only (a controller
 * orchestrating two modules' services for one response) — TenantService and
 * SubscriptionService/TenantResource stay decoupled from each other; neither
 * module's Domain/Application layer references the other in this direction.
 */
final class TenantAdminController extends Controller
{
    public function __construct(
        private readonly TenantService $tenants,
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function index(): JsonResponse
    {
        Gate::authorize(PermissionSlug::TenantsManage->value);

        $tenants = $this->tenants->listAll();
        $subscriptions = $this->subscriptions->manyFor($tenants->pluck('id')->all());

        $data = $tenants->map(function (Tenant $tenant) use ($subscriptions): array {
            $subscription = $subscriptions->get($tenant->id);

            return [
                'tenant' => TenantResource::make($tenant),
                'subscription' => $subscription !== null
                    ? TenantSubscriptionResource::make($subscription)
                    : null,
            ];
        });

        return ApiResponse::success($data);
    }

    public function suspend(Tenant $tenant): JsonResponse
    {
        Gate::authorize(PermissionSlug::TenantsManage->value);

        return ApiResponse::success(TenantResource::make($this->tenants->suspend($tenant)));
    }

    public function reactivate(Tenant $tenant): JsonResponse
    {
        Gate::authorize(PermissionSlug::TenantsManage->value);

        return ApiResponse::success(TenantResource::make($this->tenants->reactivate($tenant)));
    }
}
