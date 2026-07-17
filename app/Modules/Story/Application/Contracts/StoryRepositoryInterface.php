<?php

declare(strict_types=1);

namespace App\Modules\Story\Application\Contracts;

use App\Modules\Story\Domain\Models\Story;
use App\Modules\Story\Domain\Models\StoryView;
use Illuminate\Support\Collection;

interface StoryRepositoryInterface
{
    public function create(array $attributes): Story;

    public function findById(int $id): ?Story;

    /**
     * @return Collection<int, Story>
     */
    public function activeFeed(): Collection;

    /**
     * @return Collection<int, Story>
     */
    public function activeForAuthor(int $authorId): Collection;

    /**
     * @return Collection<int, Story>
     */
    public function expired(): Collection;

    public function delete(Story $story): void;

    public function recordView(int $storyId, int $viewerId): void;

    /**
     * @return Collection<int, StoryView>
     */
    public function viewersFor(int $storyId): Collection;

    /**
     * @param  list<int>  $storyIds
     * @return list<int>
     */
    public function viewedStoryIds(int $viewerId, array $storyIds): array;
}
