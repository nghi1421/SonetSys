<?php

declare(strict_types=1);

namespace App\Modules\Follow\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FollowUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar_url' => $this->avatar_url,
            'is_following' => (bool) ($this->is_following ?? false),
        ];
    }
}
