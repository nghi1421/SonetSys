<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Resources;

use App\Core\Storage\Application\Services\StorageService;
use App\Modules\Feed\Domain\Enums\MediaType;
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
            'liked_by_me' => (bool) ($this->liked_by_me ?? false),
            'author' => [
                'id' => $this->whenLoaded('author', fn () => $this->author->id),
                'name' => $this->whenLoaded('author', fn () => $this->author->name),
            ],
            'shared_post' => $this->shared_post_id !== null ? self::make($this->sharedPost) : null,
            'media_type' => $this->media_type?->value,
            'media_url' => in_array($this->media_type, [MediaType::Image, MediaType::Video], true) && $this->media_path !== null
                ? app(StorageService::class)->url($this->media_disk ?? 'local', $this->media_path)
                : null,
            'sticker_key' => $this->media_type === MediaType::Sticker ? $this->media_path : null,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
