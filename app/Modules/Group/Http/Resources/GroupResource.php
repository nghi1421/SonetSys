<?php

declare(strict_types=1);

namespace App\Modules\Group\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

final class GroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'visibility' => $this->visibility->value,
            'avatar_url' => $this->avatar_path !== null ? Storage::disk('public')->url($this->avatar_path) : null,
            'cover_url' => $this->cover_path !== null ? Storage::disk('public')->url($this->cover_path) : null,
            'members_count' => $this->members_count,
            'owner' => [
                'id' => $this->whenLoaded('owner', fn () => $this->owner->id),
                'name' => $this->whenLoaded('owner', fn () => $this->owner->name),
            ],
            'viewer_membership' => $this->viewer_membership !== null ? [
                'role' => $this->viewer_membership->role->value,
                'status' => $this->viewer_membership->status->value,
            ] : null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
