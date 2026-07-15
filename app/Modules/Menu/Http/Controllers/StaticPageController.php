<?php

declare(strict_types=1);

namespace App\Modules\Menu\Http\Controllers;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Menu\Application\Services\StaticPageService;
use App\Modules\Menu\Domain\Models\StaticPage;
use App\Modules\Menu\Http\Requests\CreateStaticPageRequest;
use App\Modules\Menu\Http\Requests\UpdateStaticPageRequest;
use App\Modules\Menu\Http\Resources\StaticPageResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class StaticPageController extends Controller
{
    public function __construct(
        private readonly StaticPageService $pages,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize(PermissionSlug::MenuManage->value);

        $pages = $this->pages->listForTenant((int) $request->user()->tenant_id);

        return ApiResponse::success(StaticPageResource::collection($pages));
    }

    public function store(CreateStaticPageRequest $request): JsonResponse
    {
        Gate::authorize(PermissionSlug::MenuManage->value);

        $page = $this->pages->create($request->toDto());

        return ApiResponse::success(StaticPageResource::make($page), status: 201);
    }

    public function update(UpdateStaticPageRequest $request, StaticPage $page): JsonResponse
    {
        Gate::authorize(PermissionSlug::MenuManage->value);
        $this->ensureSameTenant($request, $page);

        $page = $this->pages->update($page, $request->toDto());

        return ApiResponse::success(StaticPageResource::make($page));
    }

    public function destroy(Request $request, StaticPage $page): JsonResponse
    {
        Gate::authorize(PermissionSlug::MenuManage->value);
        $this->ensureSameTenant($request, $page);

        $this->pages->delete($page);

        return ApiResponse::success();
    }

    private function ensureSameTenant(Request $request, StaticPage $page): void
    {
        if ($page->tenant_id !== $request->user()->tenant_id) {
            throw new ModelNotFoundException;
        }
    }
}
