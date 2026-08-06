<?php

declare(strict_types=1);

namespace App\Modules\Follow\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar_url' => $this->avatar_url,
            'cover_url' => $this->cover_url,
            'created_at' => $this->created_at->toIso8601String(),
            'followers_count' => $this->followers_count,
            'following_count' => $this->following_count,
            'is_following' => (bool) ($this->is_following ?? false),
            'is_followed_by' => (bool) ($this->is_followed_by ?? false),
            'is_blocked' => (bool) ($this->is_blocked ?? false),
            'is_blocked_by' => (bool) ($this->is_blocked_by ?? false),
            'is_premium' => $this->isPremium(),
        ];
    }
}
