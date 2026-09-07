<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Resources;

use App\Core\Storage\Application\Services\StorageService;
use App\Modules\Feed\Domain\Enums\MediaType;
use App\Modules\Song\Http\Resources\SongResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'group_id' => $this->group_id,
            'body' => $this->body,
            'visibility' => $this->visibility->value,
            'metadata' => $this->metadata,
            'likes_count' => $this->likes_count,
            'comments_count' => $this->comments_count,
            'shares_count' => $this->shares_count,
            'my_reaction' => $this->my_reaction ?? null,
            'is_sponsored' => (bool) ($this->is_sponsored ?? false),
            'is_reel' => (bool) $this->is_reel,
            'author' => [
                'id' => $this->whenLoaded('author', fn () => $this->author->id),
                'name' => $this->whenLoaded('author', fn () => $this->author->name),
                'avatar_url' => $this->whenLoaded('author', fn () => $this->author->avatar_url),
            ],
            // Always an array (never omitted) — the nested shared_post below
            // doesn't get these relations eager-loaded, and the frontend
            // contract expects hashtags/mentions to always be present.
            'hashtags' => $this->relationLoaded('hashtags') ? $this->hashtags->pluck('tag')->values() : [],
            'mentions' => $this->relationLoaded('mentions') ? $this->mentions->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])->values() : [],
            'shared_post' => $this->shared_post_id !== null ? self::make($this->sharedPost) : null,
            'song' => SongResource::make($this->whenLoaded('song')),
            'song_start_sec' => (int) $this->song_start_sec,
            'media_type' => $this->media_type?->value,
            'media_url' => in_array($this->media_type, [MediaType::Image, MediaType::Video], true) && $this->media_path !== null
                ? app(StorageService::class)->url($this->media_disk ?? 'local', $this->media_path)
                : null,
            'sticker_key' => $this->media_type === MediaType::Sticker ? $this->media_path : null,
            'location' => $this->location_name !== null ? [
                'name' => $this->location_name,
                'lat' => (float) $this->location_lat,
                'lng' => (float) $this->location_lng,
            ] : null,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
