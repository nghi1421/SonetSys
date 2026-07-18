<?php

declare(strict_types=1);

namespace App\Modules\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'other_participant' => [
                'id' => $this->other_participant->id,
                'name' => $this->other_participant->name,
                'avatar_url' => $this->other_participant->avatar_url,
            ],
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'is_unread' => (bool) ($this->is_unread ?? false),
        ];
    }
}
