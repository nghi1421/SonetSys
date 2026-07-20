<?php

declare(strict_types=1);

namespace App\Core\Settings\Http\Controllers;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Settings\Application\Services\SystemSettingService;
use App\Core\Settings\Http\Requests\UpdateSystemSettingRequest;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class SystemSettingsController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    public function show(): JsonResponse
    {
        Gate::authorize(PermissionSlug::SettingsManage->value);

        return ApiResponse::success($this->settings->current());
    }

    public function update(UpdateSystemSettingRequest $request): JsonResponse
    {
        Gate::authorize(PermissionSlug::SettingsManage->value);

        $this->settings->update($request->toDto());

        return ApiResponse::success($this->settings->current());
    }
}
