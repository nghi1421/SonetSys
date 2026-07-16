<?php

declare(strict_types=1);

namespace App\Core\Billing\Http\Controllers;

use App\Core\Auth\Http\Resources\UserResource;
use App\Core\Billing\Application\Services\TenantRegistrationService;
use App\Core\Billing\Http\Requests\RegisterTenantRequest;
use App\Core\Billing\Http\Resources\TenantSubscriptionResource;
use App\Core\Support\ApiResponse;
use App\Core\Tenancy\Http\Resources\TenantResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class TenantRegistrationController extends Controller
{
    public function __construct(
        private readonly TenantRegistrationService $registrations,
    ) {}

    public function store(RegisterTenantRequest $request): JsonResponse
    {
        $result = $this->registrations->register($request->toDto());

        return ApiResponse::success([
            'tenant' => TenantResource::make($result['tenant']),
            'admin' => UserResource::make($result['admin']->load('role')),
            'subscription' => TenantSubscriptionResource::make($result['subscription']->load('plan')),
            'token' => $result['token'],
        ], status: 201);
    }
}
