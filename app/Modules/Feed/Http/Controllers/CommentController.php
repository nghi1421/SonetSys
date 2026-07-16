<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Controllers;

use App\Core\Auth\Domain\Enums\PermissionSlug;
use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Feed\Application\Contracts\GroupAccessCheckerInterface;
use App\Modules\Feed\Application\Services\CommentService;
use App\Modules\Feed\Domain\Models\Comment;
use App\Modules\Feed\Domain\Models\Post;
use App\Modules\Feed\Http\Requests\CreateCommentRequest;
use App\Modules\Feed\Http\Requests\UpdateCommentRequest;
use App\Modules\Feed\Http\Resources\CommentResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CommentController extends Controller
{
    public function __construct(
        private readonly CommentService $comments,
        private readonly GroupAccessCheckerInterface $groupAccess,
    ) {}

    public function index(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();
        $this->ensureSameTenant($request, $post);

        if ($post->group_id !== null && ! $this->groupAccess->canView($post->group_id, (int) $user->id)) {
            throw new AuthorizationException('You must be a member of this group.');
        }

        $comments = $this->comments->listForPost($post->id, $user->id);

        return ApiResponse::success(CommentResource::collection($comments));
    }

    public function store(CreateCommentRequest $request, Post $post): JsonResponse
    {
        $user = $request->user();
        $this->ensureSameTenant($request, $post);

        if ($post->group_id !== null && ! $this->groupAccess->canInteract($post->group_id, (int) $user->id)) {
            throw new AuthorizationException('You must be a member of this group.');
        }

        $comment = $this->comments->create($request->toDto());

        return ApiResponse::success(CommentResource::make($comment->load('author')), status: 201);
    }

    public function update(UpdateCommentRequest $request, Comment $comment): JsonResponse
    {
        $user = $request->user();

        if ($comment->tenant_id !== $user->tenant_id) {
            throw new ModelNotFoundException;
        }

        if ($comment->author_id !== $user->id) {
            throw new AuthorizationException('You can only edit your own comments.');
        }

        $comment = $this->comments->update($comment, $request->toDto());

        return ApiResponse::success(CommentResource::make($comment->load('author')));
    }

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        $user = $request->user();

        if ($comment->tenant_id !== $user->tenant_id) {
            throw new ModelNotFoundException;
        }

        $isAuthor = $comment->author_id === $user->id;
        $canModerate = $user->hasPermission(PermissionSlug::CommentsDeleteAny->value);

        if (! $isAuthor && ! $canModerate) {
            throw new AuthorizationException('You cannot delete this comment.');
        }

        $this->comments->delete($comment);

        return ApiResponse::success();
    }

    private function ensureSameTenant(Request $request, Post $post): void
    {
        if ($post->tenant_id !== $request->user()->tenant_id) {
            throw new ModelNotFoundException;
        }
    }
}
