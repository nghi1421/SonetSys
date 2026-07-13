<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\Services;

use App\Modules\Feed\Application\Contracts\PostRepositoryInterface;
use App\Modules\Feed\Application\DTOs\CreatePostData;
use App\Modules\Feed\Application\DTOs\UpdatePostData;
use App\Modules\Feed\Domain\Models\Post;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class PostService
{
    public function __construct(
        private readonly PostRepositoryInterface $posts,
    ) {}

    public function create(CreatePostData $data): Post
    {
        return $this->posts->create([
            'tenant_id' => $data->tenantId,
            'author_id' => $data->authorId,
            'body' => $data->body,
            'visibility' => $data->visibility,
            'metadata' => $data->metadata,
            'published_at' => now(),
        ]);
    }

    public function update(Post $post, UpdatePostData $data): Post
    {
        return $this->posts->update($post, [
            'body' => $data->body,
            'visibility' => $data->visibility,
        ]);
    }

    public function delete(Post $post): void
    {
        $this->posts->delete($post);
    }

    /**
     * @return array{items: Collection<int, Post>, next_cursor: ?string}
     */
    public function feedForTenant(?int $tenantId, int $viewerId, ?string $cursor, int $limit = 20): array
    {
        [$afterPublishedAt, $afterId] = $this->decodeCursor($cursor);

        $posts = $this->posts->cursorPaginateForTenant($tenantId, $viewerId, $afterPublishedAt, $afterId, $limit);

        $nextCursor = null;
        if ($posts->count() === $limit) {
            $last = $posts->last();
            $nextCursor = $this->encodeCursor($last->published_at, $last->id);
        }

        return ['items' => $posts, 'next_cursor' => $nextCursor];
    }

    /**
     * @return array{0: ?Carbon, 1: ?int}
     */
    private function decodeCursor(?string $cursor): array
    {
        if ($cursor === null) {
            return [null, null];
        }

        $decoded = base64_decode($cursor, true);

        if ($decoded === false || ! str_contains($decoded, '|')) {
            return [null, null];
        }

        [$publishedAt, $id] = explode('|', $decoded, 2);

        return [Carbon::parse($publishedAt), (int) $id];
    }

    private function encodeCursor(\DateTimeInterface $publishedAt, int $id): string
    {
        return base64_encode($publishedAt->format(DATE_ATOM).'|'.$id);
    }
}
