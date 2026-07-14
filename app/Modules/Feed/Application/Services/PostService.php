<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\Services;

use App\Modules\Feed\Application\Contracts\InteractionRepositoryInterface;
use App\Modules\Feed\Application\Contracts\PostRepositoryInterface;
use App\Modules\Feed\Application\DTOs\CreatePostData;
use App\Modules\Feed\Application\DTOs\UpdatePostData;
use App\Modules\Feed\Domain\Enums\MediaType;
use App\Modules\Feed\Domain\Events\PostShared;
use App\Modules\Feed\Domain\Models\Post;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

final class PostService
{
    public function __construct(
        private readonly PostRepositoryInterface $posts,
        private readonly InteractionRepositoryInterface $interactions,
    ) {}

    public function create(CreatePostData $data): Post
    {
        $sharedPostId = null;
        $originalAuthorId = null;

        if ($data->sharedPostId !== null) {
            $referenced = $this->posts->findById($data->sharedPostId);

            if ($referenced !== null) {
                // Flatten: sharing an already-shared post points at the
                // original, so reshares never chain more than one level deep.
                $originalPostId = $referenced->shared_post_id ?? $referenced->id;
                $original = $originalPostId === $referenced->id
                    ? $referenced
                    : $this->posts->findById($originalPostId);

                if ($original !== null) {
                    $sharedPostId = $originalPostId;
                    $originalAuthorId = (int) $original->author_id;
                    $this->posts->incrementSharesCount($originalPostId);
                }
            }
        }

        $mediaType = null;
        $mediaPath = null;

        if ($data->media !== null && $data->mediaType !== null) {
            $mediaType = $data->mediaType;
            $mediaPath = $data->media->store('posts/'.$data->tenantId, 'public');
        } elseif ($data->stickerKey !== null) {
            $mediaType = MediaType::Sticker;
            $mediaPath = $data->stickerKey;
        }

        $post = $this->posts->create([
            'tenant_id' => $data->tenantId,
            'author_id' => $data->authorId,
            'shared_post_id' => $sharedPostId,
            'body' => $data->body,
            'visibility' => $data->visibility,
            'metadata' => $data->metadata,
            'media_type' => $mediaType,
            'media_path' => $mediaPath,
            'published_at' => now(),
        ]);

        if ($sharedPostId !== null && $originalAuthorId !== null && $originalAuthorId !== $data->authorId) {
            PostShared::dispatch($sharedPostId, $post->id, $data->authorId, $originalAuthorId, $data->tenantId);
        }

        return $post;
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
        if ($post->shared_post_id !== null) {
            $this->posts->decrementSharesCount($post->shared_post_id);
        }

        if (in_array($post->media_type, [MediaType::Image, MediaType::Video], true) && $post->media_path !== null) {
            Storage::disk('public')->delete($post->media_path);
        }

        $this->posts->delete($post);
    }

    /**
     * @return array{items: Collection<int, Post>, next_cursor: ?string}
     */
    public function feedForTenant(?int $tenantId, int $viewerId, ?string $cursor, int $limit = 20): array
    {
        [$afterPublishedAt, $afterId] = $this->decodeCursor($cursor);

        $posts = $this->posts->cursorPaginateForTenant($tenantId, $viewerId, $afterPublishedAt, $afterId, $limit);
        $this->markLikedByViewer($posts, $viewerId);

        $nextCursor = null;
        if ($posts->count() === $limit) {
            $last = $posts->last();
            $nextCursor = $this->encodeCursor($last->published_at, $last->id);
        }

        return ['items' => $posts, 'next_cursor' => $nextCursor];
    }

    /**
     * @param  Post|Collection<int, Post>  $posts
     */
    public function markLikedByViewer(Post|Collection $posts, int $viewerId): void
    {
        $collection = $posts instanceof Post ? collect([$posts]) : $posts;

        $likedIds = $this->interactions->likedInteractableIds($viewerId, 'post', $collection->pluck('id')->all());

        $collection->each(function (Post $post) use ($likedIds): void {
            $post->liked_by_me = in_array($post->id, $likedIds, true);
        });
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
