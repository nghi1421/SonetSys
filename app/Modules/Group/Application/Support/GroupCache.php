<?php

declare(strict_types=1);

namespace App\Modules\Group\Application\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Caches the tenant-wide group listing (no per-viewer filtering in the query
 * itself — viewer_membership is attached separately by the caller), behind a
 * Redis tag per tenant so any group mutation invalidates it directly.
 *
 * Known tradeoff: same cache-aside race as FeedCache — see that class's
 * docblock. Accepted for this app's traffic level.
 */
final class GroupCache
{
    private const TTL_SECONDS = 300;

    public function rememberList(int $tenantId, Closure $callback): mixed
    {
        return Cache::tags([$this->tag($tenantId)])->remember(
            $this->tag($tenantId).':list',
            self::TTL_SECONDS,
            $callback,
        );
    }

    public function forgetList(int $tenantId): void
    {
        Cache::tags([$this->tag($tenantId)])->flush();
    }

    private function tag(int $tenantId): string
    {
        return 'groups:tenant:'.$tenantId;
    }
}
