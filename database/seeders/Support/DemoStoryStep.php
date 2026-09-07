<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Core\Auth\Domain\Models\User;
use App\Core\Storage\Domain\Enums\MediaType;
use App\Modules\Song\Domain\Models\Song;
use App\Modules\Story\Application\DTOs\CreateStoryData;
use App\Modules\Story\Application\Services\StoryService;
use Illuminate\Support\Collection;

final class DemoStoryStep
{
    private const STORY_COUNT = 26;

    private const VIDEO_STORY_CHANCE = 25;

    private const CAPTION_CHANCE = 40;

    private const SONG_CHANCE = 40;

    private const DEFAULT_SONG_DURATION_SEC = 15;

    public function __construct(
        private readonly StoryService $stories,
        private readonly DemoMediaLibrary $media,
    ) {}

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Song>  $songs
     */
    public function run(Collection $users, Collection $songs): void
    {
        $images = $this->media->storyImages();
        $videos = $this->media->videos();

        if ($images === [] && $videos === []) {
            return;
        }

        for ($i = 0; $i < self::STORY_COUNT; $i++) {
            $useVideo = $videos !== [] && ($images === [] || fake()->boolean(self::VIDEO_STORY_CHANCE));
            $pool = $useVideo ? $videos : $images;
            $song = $songs->isNotEmpty() && fake()->boolean(self::SONG_CHANCE) ? $songs->random() : null;

            $this->stories->create(new CreateStoryData(
                authorId: $users->random()->id,
                media: $this->media->asUploadedFile(fake()->randomElement($pool)),
                mediaType: $useVideo ? MediaType::Video : MediaType::Image,
                caption: fake()->boolean(self::CAPTION_CHANCE) ? fake()->randomElement(DemoContent::storyCaptions()) : null,
                songId: $song?->id,
                songStartSec: $song !== null
                    ? fake()->numberBetween(0, max(($song->duration_sec ?? self::DEFAULT_SONG_DURATION_SEC) - 1, 0))
                    : 0,
            ));
        }
    }
}
