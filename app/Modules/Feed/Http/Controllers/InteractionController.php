<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Controllers;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Feed\Application\Contracts\GroupAccessCheckerInterface;
use App\Modules\Feed\Application\Services\InteractionService;
use App\Modules\Feed\Domain\Models\Comment;
use App\Modules\Feed\Domain\Models\Post;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InteractionController extends Controller
{
    public function __construct(
        private readonly InteractionService $interactions,
        private readonly GroupAccessCheckerInterface $groupAccess,
    ) {}

    public function togglePostLike(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if ($post->tenant_id !== $user->tenant_id) {
            throw new ModelNotFoundException;
        }

        if ($post->group_id !== null && ! $this->groupAccess->canInteract($post->group_id, (int) $user->id)) {
            throw new AuthorizationException('You must be a member of this group.');
        }

        return ApiResponse::success(
            $this->interactions->toggleLike('post', $post->id, $user->id, $user->tenant_id),
        );
    }

    public function toggleCommentLike(Request $request, Comment $comment): JsonResponse
    {
        $user = $request->user();

        if ($comment->tenant_id !== $user->tenant_id) {
            throw new ModelNotFoundException;
        }

        $groupId = $comment->post?->group_id;

        if ($groupId !== null && ! $this->groupAccess->canInteract($groupId, (int) $user->id)) {
            throw new AuthorizationException('You must be a member of this group.');
        }

        return ApiResponse::success(
            $this->interactions->toggleLike('comment', $comment->id, $user->id, $user->tenant_id),
        );
    }
}
