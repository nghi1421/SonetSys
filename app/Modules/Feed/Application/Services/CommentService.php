<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\Services;

use App\Modules\Feed\Application\Contracts\CommentRepositoryInterface;
use App\Modules\Feed\Application\Contracts\PostRepositoryInterface;
use App\Modules\Feed\Application\DTOs\CreateCommentData;
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

            if ($parentId !== null) {
                $parent = $this->comments->findById($parentId);

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
