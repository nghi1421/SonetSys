<?php

declare(strict_types=1);

namespace App\Core\Support;

use Closure;
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;

/**
 * Wraps Cache::tags() so callers get real tag-based invalidation on taggable
 * stores (redis, memcached, array) but degrade to plain caching on stores
 * that don't support tags (database, file) — e.g. local dev without Redis
 * running. On a non-taggable store, forget() is a no-op and entries simply
 * expire via TTL instead of being invalidated on mutation.
 */
final class TaggableCache
{
    public static function remember(array $tags, string $key, int $ttl, Closure $callback): mixed
    {
        if (! self::supportsTags()) {
            return Cache::remember($key, $ttl, $callback);
        }

        return Cache::tags($tags)->remember($key, $ttl, $callback);
    }

    public static function forget(array $tags): void
    {
        if (! self::supportsTags()) {
            return;
        }

        Cache::tags($tags)->flush();
    }

    private static function supportsTags(): bool
    {
        return Cache::getStore() instanceof TaggableStore;
    }
}
