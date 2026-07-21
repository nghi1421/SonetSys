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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class InteractionController extends Controller
{
    public function __construct(
        private readonly InteractionService $interactions,
        private readonly GroupAccessCheckerInterface $groupAccess,
    ) {}

    public function togglePostLike(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if ($post->group_id !== null && ! $this->groupAccess->canInteract($post->group_id, (int) $user->id)) {
            throw new AuthorizationException('You must be a member of this group.');
        }

        $type = $this->resolveReactionType($request);

        return ApiResponse::success(
            $this->interactions->react('post', $post->id, $user->id, $type),
        );
    }

    public function toggleCommentLike(Request $request, Comment $comment): JsonResponse
    {
        $user = $request->user();

        $groupId = $comment->post?->group_id;

        if ($groupId !== null && ! $this->groupAccess->canInteract($groupId, (int) $user->id)) {
            throw new AuthorizationException('You must be a member of this group.');
        }

        $type = $this->resolveReactionType($request);

        return ApiResponse::success(
            $this->interactions->react('comment', $comment->id, $user->id, $type),
        );
    }

    private function resolveReactionType(Request $request): string
    {
        $request->validate([
            'type' => ['nullable', 'string', Rule::exists('reaction_types', 'key')],
        ]);

        return $request->input('type') ?? 'like';
    }
}
