<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'parent_id' => $this->parent_id,
            'body' => $this->body,
            'likes_count' => $this->likes_count,
            'my_reaction' => $this->my_reaction ?? null,
            'author' => [
                'id' => $this->whenLoaded('author', fn () => $this->author->id),
                'name' => $this->whenLoaded('author', fn () => $this->author->name),
                'avatar_url' => $this->whenLoaded('author', fn () => $this->author->avatar_url),
            ],
            'hashtags' => $this->relationLoaded('hashtags') ? $this->hashtags->pluck('tag')->values() : [],
            'mentions' => $this->relationLoaded('mentions') ? $this->mentions->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])->values() : [],
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
