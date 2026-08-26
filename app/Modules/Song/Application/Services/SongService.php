<?php

declare(strict_types=1);

namespace App\Modules\Song\Application\Services;

use App\Core\Storage\Application\Services\MediaService;
use App\Core\Storage\Application\Services\StorageService;
use App\Core\Storage\Domain\Enums\MediaType;
use App\Modules\Song\Application\Contracts\SongRepositoryInterface;
use App\Modules\Song\Application\DTOs\CreateSongData;
use App\Modules\Song\Application\DTOs\UpdateSongData;
use App\Modules\Song\Domain\Models\Song;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SongService
{
    public function __construct(
        private readonly SongRepositoryInterface $songs,
        private readonly StorageService $storage,
        private readonly MediaService $media,
    ) {}

    /**
     * @return Collection<int, Song>
     */
    public function all(): Collection
    {
        return $this->songs->all();
    }

    /**
     * @return Collection<int, Song>
     */
    public function search(?string $query, int $limit): Collection
    {
        return $this->songs->search($query, $limit);
    }

    public function create(CreateSongData $data, int $uploadedBy): Song
    {
        $storedAudio = $this->storage->store($data->audio, 'songs');
        $storedCover = $data->cover !== null ? $this->storage->store($data->cover, 'songs/covers') : null;

        return DB::transaction(function () use ($data, $uploadedBy, $storedAudio, $storedCover): Song {
            $song = $this->songs->create([
                'title' => $data->title,
                'artist' => $data->artist,
                'audio_disk' => $storedAudio['disk'],
                'audio_path' => $storedAudio['path'],
                'cover_disk' => $storedCover['disk'] ?? null,
                'cover_path' => $storedCover['path'] ?? null,
                'duration_sec' => $data->durationSec,
                'uploaded_by' => $uploadedBy,
            ]);

            $this->media->attach($storedAudio, $uploadedBy, MediaType::Audio, $data->audio, $song);

            return $song;
        });
    }

    public function update(Song $song, UpdateSongData $data, int $uploadedBy): Song
    {
        $attributes = [
            'title' => $data->title,
            'artist' => $data->artist,
            'duration_sec' => $data->durationSec,
        ];

        $oldCoverDisk = $song->cover_disk;
        $oldCoverPath = $song->cover_path;

        $storedAudio = $data->audio !== null ? $this->storage->store($data->audio, 'songs') : null;
        $storedCover = $data->cover !== null ? $this->storage->store($data->cover, 'songs/covers') : null;

        if ($storedAudio !== null) {
            $attributes['audio_disk'] = $storedAudio['disk'];
            $attributes['audio_path'] = $storedAudio['path'];
        }

        if ($storedCover !== null) {
            $attributes['cover_disk'] = $storedCover['disk'];
            $attributes['cover_path'] = $storedCover['path'];
        }

        $song = DB::transaction(function () use ($song, $attributes, $data, $uploadedBy, $storedAudio): Song {
            $song = $this->songs->update($song, $attributes);

            if ($storedAudio !== null) {
                $this->media->deleteForMediable($song);
                $this->media->attach($storedAudio, $uploadedBy, MediaType::Audio, $data->audio, $song);
            }

            return $song;
        });

        if ($storedCover !== null && $oldCoverDisk !== null && $oldCoverPath !== null) {
            $this->storage->delete($oldCoverDisk, $oldCoverPath);
        }

        return $song;
    }

    public function delete(Song $song): void
    {
        $this->media->deleteForMediable($song);

        if ($song->cover_disk !== null && $song->cover_path !== null) {
            $this->storage->delete($song->cover_disk, $song->cover_path);
        }

        $this->songs->delete($song);
    }
}
