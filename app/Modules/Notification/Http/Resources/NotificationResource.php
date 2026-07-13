<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'actor' => [
                'id' => $this->whenLoaded('actor', fn () => $this->actor?->id),
                'name' => $this->whenLoaded('actor', fn () => $this->actor?->name),
            ],
            'data' => $this->notifiable_data,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
