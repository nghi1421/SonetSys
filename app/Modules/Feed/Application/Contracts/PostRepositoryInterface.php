<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\Contracts;

use App\Modules\Feed\Domain\Models\Post;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface PostRepositoryInterface
{
    public function create(array $attributes): Post;

    public function findById(int $id): ?Post;

    /**
     * @return Collection<int, Post>
     */
    public function cursorPaginate(
        int $viewerId,
        ?Carbon $afterPublishedAt,
        ?int $afterId,
        int $limit,
    ): Collection;

    /**
     * @return Collection<int, Post>
     */
    public function cursorPaginateForGroup(
        int $groupId,
        ?Carbon $afterPublishedAt,
        ?int $afterId,
        int $limit,
    ): Collection;

    /**
     * @return Collection<int, Post>
     */
    public function cursorPaginateForAuthor(
        int $authorId,
        int $viewerId,
        ?Carbon $afterPublishedAt,
        ?int $afterId,
        int $limit,
    ): Collection;

    public function update(Post $post, array $attributes): Post;

    public function delete(Post $post): void;

    public function incrementCommentsCount(int $postId): void;

    public function decrementCommentsCount(int $postId): void;

    public function incrementSharesCount(int $postId): void;

    public function decrementSharesCount(int $postId): void;

    public function count(): int;
}
