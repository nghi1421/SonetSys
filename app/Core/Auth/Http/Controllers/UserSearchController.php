<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Controllers;

use App\Core\Auth\Application\Services\UserService;
use App\Core\Auth\Domain\Models\User;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public (auth:sanctum-only, no permission gate) user-listing actions —
 * keyword search (used by the Mention picker) and the newest-members
 * widget — deliberately separate from the admin-gated
 * UserController::index() listing.
 */
final class UserSearchController extends Controller
{
    public function __construct(
        private readonly UserService $users,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $users = $this->users->search($validated['q']);

        return ApiResponse::success($users->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'avatar_url' => $user->avatar_url,
        ])->values());
    }

    public function recent(): JsonResponse
    {
        $users = $this->users->recent();

        return ApiResponse::success($users->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'avatar_url' => $user->avatar_url,
            'created_at' => $user->created_at->toIso8601String(),
        ])->values());
    }
}
