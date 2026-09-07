<?php

declare(strict_types=1);

namespace App\Modules\Story\Infrastructure\Repositories;

use App\Modules\Story\Application\Contracts\StoryRepositoryInterface;
use App\Modules\Story\Domain\Models\Story;
use App\Modules\Story\Domain\Models\StoryView;
use Illuminate\Support\Collection;

final class EloquentStoryRepository implements StoryRepositoryInterface
{
    public function create(array $attributes): Story
    {
        return Story::query()->create($attributes)->refresh();
    }

    public function findById(int $id): ?Story
    {
        return Story::query()->find($id);
    }

    public function activeFeed(): Collection
    {
        return Story::query()
            ->where('expires_at', '>', now())
            ->withCount('views')
            ->with(['author', 'song'])
            ->orderByDesc('published_at')
            ->get();
    }

    public function activeForAuthor(int $authorId): Collection
    {
        return Story::query()
            ->where('author_id', $authorId)
            ->where('expires_at', '>', now())
            ->withCount('views')
            ->orderByDesc('published_at')
            ->get();
    }

    public function expired(): Collection
    {
        return Story::query()->where('expires_at', '<=', now())->get();
    }

    public function delete(Story $story): void
    {
        $story->delete();
    }

    public function recordView(int $storyId, int $viewerId): void
    {
        StoryView::query()->firstOrCreate(
            ['story_id' => $storyId, 'viewer_id' => $viewerId],
            ['viewed_at' => now()],
        );
    }

    public function viewersFor(int $storyId): Collection
    {
        return StoryView::query()
            ->where('story_id', $storyId)
            ->with('viewer')
            ->orderByDesc('viewed_at')
            ->get();
    }

    public function viewedStoryIds(int $viewerId, array $storyIds): array
    {
        if ($storyIds === []) {
            return [];
        }

        return StoryView::query()
            ->where('viewer_id', $viewerId)
            ->whereIn('story_id', $storyIds)
            ->pluck('story_id')
            ->all();
    }
}
