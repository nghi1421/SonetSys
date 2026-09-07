<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Feed\Application\DTOs\CreatePostData;
use App\Modules\Feed\Application\Services\PostService;
use App\Modules\Feed\Domain\Enums\MediaType;
use App\Modules\Feed\Domain\Enums\PostVisibility;
use App\Modules\Song\Domain\Models\Song;
use Illuminate\Support\Collection;

final class DemoReelStep
{
    private const REEL_COUNT = 18;

    private const SONG_CHANCE = 45;

    private const DEFAULT_SONG_DURATION_SEC = 15;

    public function __construct(
        private readonly PostService $posts,
        private readonly DemoPostEngagement $engagement,
        private readonly DemoMediaLibrary $media,
    ) {}

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Song>  $songs
     */
    public function run(Collection $users, Collection $songs): void
    {
        $videos = $this->media->videos();

        if ($videos === []) {
            return;
        }

        for ($i = 0; $i < self::REEL_COUNT; $i++) {
            $author = $users->random();
            $song = $songs->isNotEmpty() && fake()->boolean(self::SONG_CHANCE) ? $songs->random() : null;

            // Mirrors CreateReelRequest::toDto() exactly — reels are always
            // Members visibility, regardless of the author's usual mix.
            $post = $this->posts->create(new CreatePostData(
                body: fake()->randomElement(DemoContent::reelCaptions()),
                visibility: PostVisibility::Members,
                authorId: $author->id,
                media: $this->media->asUploadedFile(fake()->randomElement($videos)),
                mediaType: MediaType::Video,
                isReel: true,
                songId: $song?->id,
                songStartSec: $song !== null
                    ? fake()->numberBetween(0, max(($song->duration_sec ?? self::DEFAULT_SONG_DURATION_SEC) - 1, 0))
                    : 0,
            ));

            $this->engagement->addComments($post, $users);
            $this->engagement->addReactions($post, $users);
        }
    }
}
