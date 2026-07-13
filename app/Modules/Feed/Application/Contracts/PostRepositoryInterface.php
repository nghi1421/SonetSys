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
    public function cursorPaginateForTenant(
        ?int $tenantId,
        int $viewerId,
        ?Carbon $afterPublishedAt,
        ?int $afterId,
        int $limit,
    ): Collection;

    public function incrementCommentsCount(int $postId): void;
}
