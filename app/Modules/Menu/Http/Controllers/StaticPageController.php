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
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class StaticPageController extends Controller
{
    public function __construct(
        private readonly StaticPageService $pages,
    ) {}

    public function index(): JsonResponse
    {
        Gate::authorize(PermissionSlug::MenuManage->value);

        $pages = $this->pages->list();

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

        $page = $this->pages->update($page, $request->toDto());

        return ApiResponse::success(StaticPageResource::make($page));
    }

    public function destroy(StaticPage $page): JsonResponse
    {
        Gate::authorize(PermissionSlug::MenuManage->value);

        $this->pages->delete($page);

        return ApiResponse::success();
    }
}
