<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Controllers;

use App\Core\Auth\Application\Services\UserService;
use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Auth\Domain\Models\User;
use App\Core\Auth\Http\Requests\UpdateUserRequest;
use App\Core\Auth\Http\Resources\UserResource;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class UserController extends Controller
{
    public function __construct(
        private readonly UserService $users,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize(PermissionSlug::UsersView->value);

        $paginator = $this->users->list((int) $request->query('page', 1));

        return ApiResponse::success(UserResource::collection($paginator->items()), [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        Gate::authorize(PermissionSlug::UsersManage->value);

        if ($user->id === $request->user()->id) {
            throw new AuthorizationException('You cannot change your own role or status.');
        }

        $user = $this->users->update($user, $request->toDto());

        return ApiResponse::success(UserResource::make($user->load('role')));
    }
}
