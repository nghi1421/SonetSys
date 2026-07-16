<?php

declare(strict_types=1);

namespace App\Core\Storage\Http\Controllers;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Storage\Application\Services\StorageConfigService;
use App\Core\Storage\Http\Requests\UpdateStorageConfigRequest;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class StorageSettingsController extends Controller
{
    public function __construct(
        private readonly StorageConfigService $config,
    ) {}

    public function show(Request $request): JsonResponse
    {
        Gate::authorize(PermissionSlug::StorageManage->value);

        return ApiResponse::success($this->config->currentFor((int) $request->user()->tenant_id));
    }

    public function update(UpdateStorageConfigRequest $request): JsonResponse
    {
        Gate::authorize(PermissionSlug::StorageManage->value);

        $tenantId = (int) $request->user()->tenant_id;
        $this->config->update($tenantId, $request->toDto());

        return ApiResponse::success($this->config->currentFor($tenantId));
    }
}
