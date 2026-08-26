<?php

declare(strict_types=1);

namespace App\Modules\Song\Http\Resources;

use App\Core\Storage\Application\Services\StorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SongResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $storage = app(StorageService::class);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'artist' => $this->artist,
            'audio_url' => $storage->url($this->audio_disk, $this->audio_path),
            'cover_url' => $this->cover_disk !== null && $this->cover_path !== null
                ? $storage->url($this->cover_disk, $this->cover_path)
                : null,
            'duration_sec' => $this->duration_sec,
        ];
    }
}
