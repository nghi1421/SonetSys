<?php

declare(strict_types=1);

namespace App\Modules\Search\Application\Services;

use App\Core\Auth\Application\Services\UserService;
use App\Core\Auth\Domain\Models\User;
use App\Modules\Feed\Domain\Enums\PostVisibility;
use App\Modules\Feed\Domain\Models\Hashtag;
use App\Modules\Feed\Domain\Models\Post;
use App\Modules\Feed\Http\Resources\PostResource;
use App\Modules\Group\Domain\Enums\GroupVisibility;
use App\Modules\Group\Domain\Models\Group;
use App\Modules\Group\Http\Resources\GroupResource;
use Illuminate\Database\Eloquent\Collection;

/**
 * Runs four independent, capped case-insensitive substring queries (plain
 * LOWER()+LIKE, not ILIKE, so this runs unchanged on the sqlite connection
 * the test suite uses — see EloquentUserRepository::search()) and returns a
 * single combined preview shape — deliberately NOT full-text/tsvector
 * search, and deliberately NOT paginated per-category (see plan: "a single
 * capped combined response per query").
 */
final readonly class SearchService
{
    private const RESULT_LIMIT = 8;

    public function __construct(
        private UserService $users,
    ) {}

    /**
     * @return array{posts: array, users: array, groups: array, hashtags: array}
     */
    public function search(string $query, int $viewerId): array
    {
        return [
            'posts' => PostResource::collection($this->searchPosts($query))->resolve(),
            'users' => $this->users->search($query)->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url,
            ])->values()->all(),
            'groups' => GroupResource::collection($this->searchGroups($query))->resolve(),
            'hashtags' => $this->searchHashtags($query)->pluck('tag')->values()->all(),
        ];
    }

    /**
     * @return Collection<int, Post>
     */
    private function searchPosts(string $query): Collection
    {
        // Public, non-group posts only — searching inside groups or
        // Members/Private-visibility content is explicitly out of scope.
        return Post::query()
            ->whereNull('group_id')
            ->where('visibility', PostVisibility::Public->value)
            ->whereRaw('LOWER(body) LIKE ?', ['%'.mb_strtolower($query).'%'])
            ->orderByDesc('published_at')
            ->limit(self::RESULT_LIMIT)
            ->with(['author', 'hashtags', 'mentions'])
            ->get();
    }

    /**
     * @return Collection<int, Group>
     */
    private function searchGroups(string $query): Collection
    {
        return Group::query()
            ->where('visibility', GroupVisibility::Public->value)
            ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($query).'%'])
            ->orderBy('name')
            ->limit(self::RESULT_LIMIT)
            ->with('owner')
            ->get();
    }

    /**
     * @return Collection<int, Hashtag>
     */
    private function searchHashtags(string $query): Collection
    {
        return Hashtag::query()
            ->whereRaw('LOWER(tag) LIKE ?', ['%'.mb_strtolower($query).'%'])
            ->orderBy('tag')
            ->limit(self::RESULT_LIMIT)
            ->get();
    }
}
