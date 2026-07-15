<?php

declare(strict_types=1);

namespace App\Modules\Group\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class GroupMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'joined_at' => $this->joined_at?->toIso8601String(),
            'user' => [
                'id' => $this->whenLoaded('user', fn () => $this->user->id),
                'name' => $this->whenLoaded('user', fn () => $this->user->name),
                'avatar_url' => $this->whenLoaded('user', fn () => $this->user->avatar_url),
            ],
        ];
    }
}
