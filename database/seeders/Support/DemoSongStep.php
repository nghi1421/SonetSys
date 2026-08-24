<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Song\Application\DTOs\CreateSongData;
use App\Modules\Song\Application\Services\SongService;
use App\Modules\Song\Domain\Models\Song;
use Illuminate\Support\Collection;

final class DemoSongStep
{
    public function __construct(
        private readonly SongService $songs,
        private readonly DemoMediaLibrary $media,
    ) {}

    /**
     * @param  Collection<int, User>  $users
     * @return Collection<int, Song>
     */
    public function run(Collection $users): Collection
    {
        $files = $this->media->songs();

        if ($files === []) {
            return collect();
        }

        $titles = DemoContent::songTitles();
        $artists = DemoContent::songArtists();
        $uploader = $users->random();

        return collect($files)->map(function (string $file, int $index) use ($titles, $artists, $uploader): Song {
            return $this->songs->create(
                new CreateSongData(
                    title: $titles[$index % count($titles)],
                    audio: $this->media->asUploadedFile($file),
                    artist: $artists[$index % count($artists)],
                ),
                $uploader->id,
            );
        })->values();
    }
}
