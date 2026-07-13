<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\Services;

use App\Modules\Feed\Application\Contracts\CommentRepositoryInterface;
use App\Modules\Feed\Application\Contracts\PostRepositoryInterface;
use App\Modules\Feed\Application\DTOs\CreateCommentData;
use App\Modules\Feed\Domain\Events\CommentPosted;
use App\Modules\Feed\Domain\Models\Comment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CommentService
{
    public function __construct(
        private readonly CommentRepositoryInterface $comments,
        private readonly PostRepositoryInterface $posts,
    ) {}

    public function create(CreateCommentData $data): Comment
    {
        return DB::transaction(function () use ($data): Comment {
            $parentId = $data->parentId;
            $directParentAuthorId = null;

            if ($parentId !== null) {
                $parent = $this->comments->findById($parentId);
                // Capture who the user actually clicked "reply" on, before
                // flattening — that's the correct notification recipient.
                $directParentAuthorId = $parent?->author_id;

                // Depth cap = 1: a reply to a reply flattens onto the original parent.
                if ($parent !== null && $parent->parent_id !== null) {
                    $parentId = $parent->parent_id;
                }
            }

            $comment = $this->comments->create([
                'tenant_id' => $data->tenantId,
                'post_id' => $data->postId,
                'parent_id' => $parentId,
                'author_id' => $data->authorId,
                'body' => $data->body,
            ]);

            $this->posts->incrementCommentsCount($data->postId);

            $post = $this->posts->findById($data->postId);

            CommentPosted::dispatch(
                $comment->id,
                $data->postId,
                $data->authorId,
                $directParentAuthorId,
                (int) $post->author_id,
                $data->tenantId,
            );

            return $comment;
        });
    }

    /**
     * @return Collection<int, Comment>
     */
    public function listForPost(int $postId): Collection
    {
        return $this->comments->listForPost($postId);
    }
}
