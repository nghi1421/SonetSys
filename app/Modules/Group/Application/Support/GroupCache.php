<?php

declare(strict_types=1);

namespace App\Modules\Group\Application\Support;

use App\Core\Support\TaggableCache;
use Closure;

/**
 * Caches the site-wide group listing (no per-viewer filtering in the query
 * itself — viewer_membership is attached separately by the caller), behind a
 * cache tag so any group mutation invalidates it directly. Tagging requires
 * a taggable store; see TaggableCache for the fallback on stores that don't
 * support it (e.g. database).
 *
 * Known tradeoff: same cache-aside race as FeedCache — see that class's
 * docblock. Accepted for this app's traffic level.
 */
final class GroupCache
{
    private const TTL_SECONDS = 300;

    public function rememberList(Closure $callback): mixed
    {
        return TaggableCache::remember(
            [$this->tag()],
            $this->tag().':list',
            self::TTL_SECONDS,
            $callback,
        );
    }

    public function forgetList(): void
    {
        TaggableCache::forget([$this->tag()]);
    }

    private function tag(): string
    {
        return 'groups';
    }
}
