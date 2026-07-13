<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Http\Controllers;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Support\ApiResponse;
use App\Core\Tenancy\Application\Services\TenantService;
use App\Core\Tenancy\Http\Requests\CreateTenantRequest;
use App\Core\Tenancy\Http\Resources\TenantResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class TenantController extends Controller
{
    public function __construct(
        private readonly TenantService $tenants,
    ) {}

    public function store(CreateTenantRequest $request): JsonResponse
    {
        Gate::authorize(PermissionSlug::TenantsManage->value);

        $tenant = $this->tenants->create($request->toDto());

        return ApiResponse::success(TenantResource::make($tenant), status: 201);
    }
}
