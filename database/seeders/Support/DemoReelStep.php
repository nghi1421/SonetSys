<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Feed\Application\DTOs\CreatePostData;
use App\Modules\Feed\Application\Services\PostService;
use App\Modules\Feed\Domain\Enums\MediaType;
use App\Modules\Feed\Domain\Enums\PostVisibility;
use Illuminate\Support\Collection;

final class DemoReelStep
{
    private const REEL_COUNT = 18;

    public function __construct(
        private readonly PostService $posts,
        private readonly DemoPostEngagement $engagement,
        private readonly DemoMediaLibrary $media,
    ) {}

    /**
     * @param  Collection<int, User>  $users
     */
    public function run(Collection $users): void
    {
        $videos = $this->media->videos();

        if ($videos === []) {
            return;
        }

        for ($i = 0; $i < self::REEL_COUNT; $i++) {
            $author = $users->random();

            // Mirrors CreateReelRequest::toDto() exactly — reels are always
            // Members visibility, regardless of the author's usual mix.
            $post = $this->posts->create(new CreatePostData(
                body: fake()->randomElement(DemoContent::reelCaptions()),
                visibility: PostVisibility::Members,
                authorId: $author->id,
                media: $this->media->asUploadedFile(fake()->randomElement($videos)),
                mediaType: MediaType::Video,
                isReel: true,
            ));

            $this->engagement->addComments($post, $users);
            $this->engagement->addReactions($post, $users);
        }
    }
}
