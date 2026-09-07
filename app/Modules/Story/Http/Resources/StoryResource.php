<?php

declare(strict_types=1);

namespace App\Modules\Story\Http\Resources;

use App\Core\Storage\Application\Services\StorageService;
use App\Modules\Song\Http\Resources\SongResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isOwner = $request->user()?->id === $this->author_id;

        return [
            'id' => $this->id,
            'author' => [
                'id' => $this->whenLoaded('author', fn () => $this->author->id),
                'name' => $this->whenLoaded('author', fn () => $this->author->name),
            ],
            'media_type' => $this->media_type->value,
            'media_url' => app(StorageService::class)->url($this->media_disk, $this->media_path),
            'caption' => $this->caption,
            'song' => SongResource::make($this->whenLoaded('song')),
            'song_start_sec' => (int) $this->song_start_sec,
            'published_at' => $this->published_at->toIso8601String(),
            'expires_at' => $this->expires_at->toIso8601String(),
            'viewed_by_me' => (bool) ($this->viewed_by_me ?? false),
            'views_count' => $isOwner ? ($this->views_count ?? 0) : null,
        ];
    }
}
