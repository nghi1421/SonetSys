<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status->value,
            'role' => [
                'id' => $this->whenLoaded('role', fn () => $this->role->id),
                'slug' => $this->whenLoaded('role', fn () => $this->role->slug),
                'name' => $this->whenLoaded('role', fn () => $this->role->name),
            ],
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
