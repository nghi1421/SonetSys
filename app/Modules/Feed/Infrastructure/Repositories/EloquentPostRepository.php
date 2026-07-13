<?php

declare(strict_types=1);

namespace App\Modules\Feed\Infrastructure\Repositories;

use App\Modules\Feed\Application\Contracts\PostRepositoryInterface;
use App\Modules\Feed\Domain\Enums\PostVisibility;
use App\Modules\Feed\Domain\Models\Post;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class EloquentPostRepository implements PostRepositoryInterface
{
    public function create(array $attributes): Post
    {
        // likes_count/comments_count are DB-defaulted, not mass-assignable
        // (counters only ever change via atomic increment/decrement) — refresh()
        // pulls those defaults into the in-memory model after insert.
        return Post::query()->create($attributes)->refresh();
    }

    public function findById(int $id): ?Post
    {
        return Post::query()->find($id);
    }

    public function cursorPaginateForTenant(
        ?int $tenantId,
        int $viewerId,
        ?Carbon $afterPublishedAt,
        ?int $afterId,
        int $limit,
    ): Collection {
        return Post::query()
            ->when(
                $tenantId === null,
                fn ($query) => $query->whereNull('tenant_id'),
                fn ($query) => $query->where('tenant_id', $tenantId),
            )
            ->where(function ($query) use ($viewerId): void {
                $query->where('visibility', '!=', PostVisibility::Private->value)
                    ->orWhere('author_id', $viewerId);
            })
            ->when(
                $afterPublishedAt !== null && $afterId !== null,
                fn ($query) => $query->where(function ($inner) use ($afterPublishedAt, $afterId): void {
                    $inner->where('published_at', '<', $afterPublishedAt)
                        ->orWhere(function ($tie) use ($afterPublishedAt, $afterId): void {
                            $tie->where('published_at', $afterPublishedAt)->where('id', '<', $afterId);
                        });
                }),
            )
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->with('author')
            ->get();
    }

    public function incrementCommentsCount(int $postId): void
    {
        Post::query()->whereKey($postId)->increment('comments_count');
    }
}
