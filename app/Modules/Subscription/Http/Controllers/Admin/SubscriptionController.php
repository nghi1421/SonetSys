<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Http\Controllers\Admin;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Subscription\Application\Services\SubscriptionService;
use App\Modules\Subscription\Domain\Models\UserSubscription;
use App\Modules\Subscription\Http\Resources\Admin\AdminSubscriptionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize(PermissionSlug::SubscriptionManage->value);

        $paginator = $this->subscriptions->adminList((int) $request->query('page', 1));

        return ApiResponse::success(AdminSubscriptionResource::collection($paginator->items()), [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function cancel(Request $request, UserSubscription $userSubscription): JsonResponse
    {
        Gate::authorize(PermissionSlug::SubscriptionManage->value);

        $admin = $request->user();

        $subscription = $this->subscriptions->adminCancel($userSubscription->id, (int) $admin->id);

        return ApiResponse::success(AdminSubscriptionResource::make($subscription));
    }
}
