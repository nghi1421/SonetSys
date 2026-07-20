<?php

declare(strict_types=1);

namespace App\Modules\Feed\Application\Support;

use App\Core\Support\TaggableCache;
use Closure;

/**
 * Caches the expensive part of feed listing (which posts, in order) behind
 * cache tags so a mutation can invalidate exactly the affected feed without
 * scanning keys. Per-viewer overlays (liked_by_me) are applied by the caller
 * after this returns, never cached here — see PostService::feed(). Tagging
 * requires a taggable store (redis, memcached, array); see TaggableCache for
 * the fallback behavior on stores that don't support it (e.g. database).
 *
 * Known tradeoff: remember()/forget() form a plain cache-aside pattern with
 * no lock. A request that starts a slow read right before a concurrent
 * mutation calls forget() can still write its (now-stale) result back after
 * the flush, reviving stale data until the next mutation or TTL expiry.
 * Accepted for this app's traffic level; add Cache::lock() around the
 * remember() calls if that race becomes a real problem.
 */
final class FeedCache
{
    private const TTL_SECONDS = 300;

    /**
     * The site feed excludes group posts but includes each viewer's own
     * Private-visibility posts, so the key must be per-viewer to avoid
     * leaking one user's private posts into another user's cached page.
     */
    public function rememberFeed(int $viewerId, ?string $cursor, Closure $callback): mixed
    {
        return TaggableCache::remember(
            [$this->feedTag()],
            $this->feedKey($viewerId, $cursor),
            self::TTL_SECONDS,
            $callback,
        );
    }

    /**
     * Group feed has no per-viewer filtering in the query itself (membership
     * is already gated at the controller), so it can be shared across viewers.
     */
    public function rememberGroupFeed(int $groupId, ?string $cursor, Closure $callback): mixed
    {
        return TaggableCache::remember(
            [$this->groupTag($groupId)],
            $this->groupKey($groupId, $cursor),
            self::TTL_SECONDS,
            $callback,
        );
    }

    /**
     * Which authors are "followed" is itself per-viewer, so this shares the
     * same tag/invalidation as rememberFeed() — no separate forget method
     * needed, forgetFeed() already flushes both on any post mutation.
     */
    public function rememberFollowingFeed(int $viewerId, ?string $cursor, Closure $callback): mixed
    {
        return TaggableCache::remember(
            [$this->feedTag()],
            $this->followingKey($viewerId, $cursor),
            self::TTL_SECONDS,
            $callback,
        );
    }

    public function forgetFeed(): void
    {
        TaggableCache::forget([$this->feedTag()]);
    }

    public function forgetGroupFeed(int $groupId): void
    {
        TaggableCache::forget([$this->groupTag($groupId)]);
    }

    private function feedTag(): string
    {
        return 'feed';
    }

    private function groupTag(int $groupId): string
    {
        return 'feed:group:'.$groupId;
    }

    private function feedKey(int $viewerId, ?string $cursor): string
    {
        return $this->feedTag().':viewer:'.$viewerId.':cursor:'.($cursor ?? 'root');
    }

    private function followingKey(int $viewerId, ?string $cursor): string
    {
        return $this->feedTag().':following:viewer:'.$viewerId.':cursor:'.($cursor ?? 'root');
    }

    private function groupKey(int $groupId, ?string $cursor): string
    {
        return $this->groupTag($groupId).':cursor:'.($cursor ?? 'root');
    }
}
