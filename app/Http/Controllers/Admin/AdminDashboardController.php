<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Auth\Application\Contracts\UserRepositoryInterface;
use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Storage\Application\Contracts\MediaRepositoryInterface;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Feed\Application\Contracts\PostRepositoryInterface;
use App\Modules\Group\Application\Contracts\GroupRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PostRepositoryInterface $posts,
        private readonly GroupRepositoryInterface $groups,
        private readonly MediaRepositoryInterface $media,
    ) {}

    public function stats(): JsonResponse
    {
        Gate::authorize(PermissionSlug::UsersView->value);

        return ApiResponse::success([
            'users' => $this->users->count(),
            'posts' => $this->posts->count(),
            'groups' => $this->groups->count(),
            'media' => $this->media->count(),
            'storage_bytes' => $this->media->sumSize(),
        ]);
    }
}
