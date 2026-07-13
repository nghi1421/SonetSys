<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\Contracts;

use App\Modules\Feed\Domain\Models\Comment;
use Illuminate\Support\Collection;

interface CommentRepositoryInterface
{
    public function create(array $attributes): Comment;

    public function findById(int $id): ?Comment;

    /**
     * @return Collection<int, Comment>
     */
    public function listForPost(int $postId): Collection;

    public function update(Comment $comment, array $attributes): Comment;

    public function delete(Comment $comment): void;
}
