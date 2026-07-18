<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\Services;

use App\Core\Storage\Application\Services\MediaService;
use App\Core\Storage\Application\Services\StorageService;
use App\Core\Storage\Domain\Enums\MediaType as CoreMediaType;
use App\Modules\Feed\Application\Contracts\InteractionRepositoryInterface;
use App\Modules\Feed\Application\Contracts\PostRepositoryInterface;
use App\Modules\Feed\Application\DTOs\CreatePostData;
use App\Modules\Feed\Application\DTOs\UpdatePostData;
use App\Modules\Feed\Application\Support\FeedCache;
use App\Modules\Feed\Domain\Enums\MediaType;
use App\Modules\Feed\Domain\Events\PostShared;
use App\Modules\Feed\Domain\Events\UserMentioned;
use App\Modules\Feed\Domain\Models\Post;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class PostService
{
    public function __construct(
        private readonly PostRepositoryInterface $posts,
        private readonly InteractionRepositoryInterface $interactions,
        private readonly FeedCache $cache,
        private readonly StorageService $storage,
        private readonly MediaService $media,
        private readonly HashtagService $hashtags,
        private readonly MentionService $mentions,
    ) {}

    public function create(CreatePostData $data): Post
    {
        $sharedPostId = null;
        $originalAuthorId = null;
        $original = null;
        $mentionedUserIds = $this->mentions->filterRecipients($data->mentionedUserIds, $data->authorId);

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
                }
            }
        }

        $mediaType = null;
        $mediaPath = null;
        $mediaDisk = null;
        $stored = null;

        if ($data->media !== null && $data->mediaType !== null) {
            $mediaType = $data->mediaType;
            $stored = $this->storage->store($data->media, 'posts');
            $mediaPath = $stored['path'];
            $mediaDisk = $stored['disk'];
        } elseif ($data->stickerKey !== null) {
            $mediaType = MediaType::Sticker;
            $mediaPath = $data->stickerKey;
        }

        $post = DB::transaction(function () use ($data, $sharedPostId, $mediaType, $mediaPath, $mediaDisk, $stored, $mentionedUserIds): Post {
            if ($sharedPostId !== null) {
                $this->posts->incrementSharesCount($sharedPostId);
            }

            $post = $this->posts->create([
                'author_id' => $data->authorId,
                'shared_post_id' => $sharedPostId,
                'group_id' => $data->groupId,
                'body' => $data->body,
                'visibility' => $data->visibility,
                'metadata' => $data->metadata,
                'media_type' => $mediaType,
                'media_path' => $mediaPath,
                'media_disk' => $mediaDisk,
                'location_name' => $data->locationName,
                'location_lat' => $data->locationLat,
                'location_lng' => $data->locationLng,
                'published_at' => now(),
            ]);

            if ($stored !== null) {
                $this->media->attach(
                    $stored,
                    $data->authorId,
                    $this->toCoreMediaType($mediaType),
                    $data->media,
                    $post,
                );
            }

            $this->hashtags->extractAndAttach($data->body, $post);

            if ($mentionedUserIds !== []) {
                $post->mentions()->sync($mentionedUserIds);
            }

            return $post;
        });

        if ($sharedPostId !== null && $originalAuthorId !== null && $originalAuthorId !== $data->authorId) {
            PostShared::dispatch($sharedPostId, $post->id, $data->authorId, $originalAuthorId);
        }

        if ($mentionedUserIds !== []) {
            UserMentioned::dispatch('post', $post->id, $data->authorId, $mentionedUserIds);
        }

        $this->forgetFeedCache($post);
        if ($original !== null) {
            $this->forgetFeedCache($original);
        }

        return $post;
    }

    private function toCoreMediaType(MediaType $type): CoreMediaType
    {
        return match ($type) {
            MediaType::Image => CoreMediaType::Image,
            MediaType::Video => CoreMediaType::Video,
            MediaType::Sticker => CoreMediaType::File,
        };
    }

    public function update(Post $post, UpdatePostData $data): Post
    {
        $post = DB::transaction(function () use ($post, $data): Post {
            $post = $this->posts->update($post, [
                'body' => $data->body,
                'visibility' => $data->visibility,
            ]);

            // Replace-all sync — a hashtag no longer present in the edited
            // body gets detached, not just left stale. Mentions are
            // deliberately untouched here: they notify once, at creation only.
            $this->hashtags->extractAndAttach($data->body, $post);

            return $post;
        });

        $this->forgetFeedCache($post);

        return $post;
    }

    public function delete(Post $post): void
    {
        if ($post->shared_post_id !== null) {
            $this->posts->decrementSharesCount($post->shared_post_id);

            $original = $this->posts->findById($post->shared_post_id);
            if ($original !== null) {
                $this->forgetFeedCache($original);
            }
        }

        if (in_array($post->media_type, [MediaType::Image, MediaType::Video], true) && $post->media_path !== null) {
            $hadMediaRow = $this->media->deleteForMediable($post);

            if (! $hadMediaRow) {
                // Post created before the media table existed — no row to
                // clean up, but the file itself still needs deleting.
                $this->storage->delete($post->media_disk ?? 'local', $post->media_path);
            }
        }

        $this->posts->delete($post);
        $this->forgetFeedCache($post);
    }

    private function forgetFeedCache(Post $post): void
    {
        if ($post->group_id !== null) {
            $this->cache->forgetGroupFeed((int) $post->group_id);

            return;
        }

        $this->cache->forgetFeed();
    }

    /**
     * @return array{items: Collection<int, Post>, next_cursor: ?string}
     */
    public function feed(int $viewerId, ?string $cursor, int $limit = 20): array
    {
        [$afterPublishedAt, $afterId] = $this->decodeCursor($cursor);

        $posts = $this->cache->rememberFeed(
            $viewerId,
            $cursor,
            fn () => $this->posts->cursorPaginate($viewerId, $afterPublishedAt, $afterId, $limit),
        );
        $this->markReactionByViewer($posts, $viewerId);

        $nextCursor = null;
        if ($posts->count() === $limit) {
            $last = $posts->last();
            $nextCursor = $this->encodeCursor($last->published_at, $last->id);
        }

        return ['items' => $posts, 'next_cursor' => $nextCursor];
    }

    /**
     * @return array{items: Collection<int, Post>, next_cursor: ?string}
     */
    public function feedForGroup(int $groupId, int $viewerId, ?string $cursor, int $limit = 20): array
    {
        [$afterPublishedAt, $afterId] = $this->decodeCursor($cursor);

        $posts = $this->cache->rememberGroupFeed(
            $groupId,
            $cursor,
            fn () => $this->posts->cursorPaginateForGroup($groupId, $afterPublishedAt, $afterId, $limit),
        );
        $this->markReactionByViewer($posts, $viewerId);

        $nextCursor = null;
        if ($posts->count() === $limit) {
            $last = $posts->last();
            $nextCursor = $this->encodeCursor($last->published_at, $last->id);
        }

        return ['items' => $posts, 'next_cursor' => $nextCursor];
    }

    /**
     * @return array{items: Collection<int, Post>, next_cursor: ?string}
     */
    public function feedForAuthor(int $authorId, int $viewerId, ?string $cursor, int $limit = 20): array
    {
        [$afterPublishedAt, $afterId] = $this->decodeCursor($cursor);

        $posts = $this->posts->cursorPaginateForAuthor($authorId, $viewerId, $afterPublishedAt, $afterId, $limit);
        $this->markReactionByViewer($posts, $viewerId);

        $nextCursor = null;
        if ($posts->count() === $limit) {
            $last = $posts->last();
            $nextCursor = $this->encodeCursor($last->published_at, $last->id);
        }

        return ['items' => $posts, 'next_cursor' => $nextCursor];
    }

    /**
     * @return array{items: Collection<int, Post>, next_cursor: ?string}
     */
    public function feedForHashtag(string $tag, int $viewerId, ?string $cursor, int $limit = 20): array
    {
        [$afterPublishedAt, $afterId] = $this->decodeCursor($cursor);

        $posts = $this->posts->cursorPaginateForHashtag($tag, $viewerId, $afterPublishedAt, $afterId, $limit);
        $this->markReactionByViewer($posts, $viewerId);

        $nextCursor = null;
        if ($posts->count() === $limit) {
            $last = $posts->last();
            $nextCursor = $this->encodeCursor($last->published_at, $last->id);
        }

        return ['items' => $posts, 'next_cursor' => $nextCursor];
    }

    /**
     * @return array{items: Collection<int, Post>, next_cursor: ?string}
     */
    public function feedForFollowing(int $viewerId, ?string $cursor, int $limit = 20): array
    {
        [$afterPublishedAt, $afterId] = $this->decodeCursor($cursor);

        $posts = $this->cache->rememberFollowingFeed(
            $viewerId,
            $cursor,
            fn () => $this->posts->cursorPaginateForFollowing($viewerId, $afterPublishedAt, $afterId, $limit),
        );
        $this->markReactionByViewer($posts, $viewerId);

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
    public function markReactionByViewer(Post|Collection $posts, int $viewerId): void
    {
        $collection = $posts instanceof Post ? collect([$posts]) : $posts;

        $myReactions = $this->interactions->myReactionsAmong($viewerId, 'post', $collection->pluck('id')->all());

        $collection->each(function (Post $post) use ($myReactions): void {
            $post->my_reaction = $myReactions[$post->id] ?? null;
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
