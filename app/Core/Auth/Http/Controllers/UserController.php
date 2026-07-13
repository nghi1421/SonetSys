<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Controllers;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Auth\Domain\Models\User;
use App\Core\Auth\Http\Resources\UserResource;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize(PermissionSlug::UsersView->value);

        $users = User::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->with('role')
            ->paginate(25);

        return ApiResponse::success(UserResource::collection($users->items()), [
            'page' => $users->currentPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
        ]);
    }
}
