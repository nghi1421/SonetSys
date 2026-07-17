<?php

declare(strict_types=1);

namespace App\Modules\Story\Application\Services;

use App\Core\Storage\Application\Services\MediaService;
use App\Core\Storage\Application\Services\StorageService;
use App\Modules\Story\Application\Contracts\StoryRepositoryInterface;
use App\Modules\Story\Application\DTOs\CreateStoryData;
use App\Modules\Story\Domain\Models\Story;
use App\Modules\Story\Domain\Models\StoryView;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StoryService
{
    public function __construct(
        private readonly StoryRepositoryInterface $stories,
        private readonly StorageService $storage,
        private readonly MediaService $media,
    ) {}

    public function create(CreateStoryData $data): Story
    {
        $stored = $this->storage->store($data->media, 'stories');

        return DB::transaction(function () use ($data, $stored): Story {
            $story = $this->stories->create([
                'author_id' => $data->authorId,
                'media_type' => $data->mediaType,
                'media_disk' => $stored['disk'],
                'media_path' => $stored['path'],
                'caption' => $data->caption,
                'published_at' => now(),
                'expires_at' => now()->addHours(24),
            ]);

            $this->media->attach($stored, $data->authorId, $data->mediaType, $data->media, $story);

            return $story;
        });
    }

    public function delete(Story $story): void
    {
        $this->media->deleteForMediable($story);
        $this->stories->delete($story);
    }

    public function recordView(Story $story, int $viewerId): void
    {
        if ($story->author_id === $viewerId) {
            return;
        }

        $this->stories->recordView($story->id, $viewerId);
    }

    /**
     * @return Collection<int, StoryView>
     */
    public function viewers(Story $story): Collection
    {
        return $this->stories->viewersFor($story->id);
    }

    /**
     * @return Collection<int, Story>
     */
    public function listActiveFeed(int $viewerId): Collection
    {
        $stories = $this->stories->activeFeed();
        $viewedIds = $this->stories->viewedStoryIds($viewerId, $stories->pluck('id')->all());

        $stories->each(function (Story $story) use ($viewedIds): void {
            $story->viewed_by_me = in_array($story->id, $viewedIds, true);
        });

        return $stories;
    }

    /**
     * @return Collection<int, Story>
     */
    public function expired(): Collection
    {
        return $this->stories->expired();
    }
}
