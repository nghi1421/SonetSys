<?php

declare(strict_types=1);

namespace App\Modules\Menu\Http\Controllers;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Menu\Application\Services\MenuService;
use App\Modules\Menu\Domain\Models\MenuItem;
use App\Modules\Menu\Http\Requests\CreateMenuItemRequest;
use App\Modules\Menu\Http\Requests\ReorderMenuItemsRequest;
use App\Modules\Menu\Http\Requests\UpdateMenuItemRequest;
use App\Modules\Menu\Http\Resources\MenuItemResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class MenuItemController extends Controller
{
    public function __construct(
        private readonly MenuService $menu,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->menu->listForTenant((int) $request->user()->tenant_id);

        return ApiResponse::success(MenuItemResource::collection($items));
    }

    public function show(Request $request, MenuItem $menuItem): JsonResponse
    {
        $this->ensureSameTenant($request, $menuItem);

        $menuItem->loadMissing('staticPage');

        return ApiResponse::success(MenuItemResource::make($menuItem));
    }

    public function store(CreateMenuItemRequest $request): JsonResponse
    {
        Gate::authorize(PermissionSlug::MenuManage->value);

        $item = $this->menu->create($request->toDto());

        return ApiResponse::success(MenuItemResource::make($item->load('staticPage')), status: 201);
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem): JsonResponse
    {
        Gate::authorize(PermissionSlug::MenuManage->value);
        $this->ensureSameTenant($request, $menuItem);

        $item = $this->menu->update($menuItem, $request->toDto());

        return ApiResponse::success(MenuItemResource::make($item->load('staticPage')));
    }

    public function destroy(Request $request, MenuItem $menuItem): JsonResponse
    {
        Gate::authorize(PermissionSlug::MenuManage->value);
        $this->ensureSameTenant($request, $menuItem);

        if ($menuItem->is_home) {
            throw new AuthorizationException('The Home menu item cannot be deleted.');
        }

        $this->menu->delete($menuItem);

        return ApiResponse::success();
    }

    public function reorder(ReorderMenuItemsRequest $request): JsonResponse
    {
        Gate::authorize(PermissionSlug::MenuManage->value);

        $this->menu->reorder((int) $request->user()->tenant_id, $request->orderedIds());

        return ApiResponse::success();
    }

    private function ensureSameTenant(Request $request, MenuItem $menuItem): void
    {
        if ($menuItem->tenant_id !== $request->user()->tenant_id) {
            throw new ModelNotFoundException;
        }
    }
}
