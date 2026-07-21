<?php

declare(strict_types=1);

namespace App\Modules\Feed\Infrastructure\Repositories;

use App\Modules\Feed\Application\Contracts\CommentRepositoryInterface;
use App\Modules\Feed\Domain\Models\Comment;
use Illuminate\Support\Collection;

final class EloquentCommentRepository implements CommentRepositoryInterface
{
    public function create(array $attributes): Comment
    {
        // likes_count is DB-defaulted, not mass-assignable — refresh() pulls
        // it into the in-memory model after insert (see EloquentPostRepository).
        return Comment::query()->create($attributes)->refresh();
    }

    public function findById(int $id): ?Comment
    {
        return Comment::query()->find($id);
    }

    public function listForPost(int $postId): Collection
    {
        return Comment::query()
            ->where('post_id', $postId)
            ->with(['author', 'hashtags', 'mentions'])
            ->orderBy('created_at')
            ->get();
    }

    public function update(Comment $comment, array $attributes): Comment
    {
        $comment->fill($attributes)->save();

        return $comment;
    }

    public function delete(Comment $comment): void
    {
        $comment->delete();
    }
}
