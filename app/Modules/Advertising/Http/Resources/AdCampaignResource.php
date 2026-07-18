<?php

declare(strict_types=1);

namespace App\Modules\Advertising\Http\Resources;

use App\Modules\Feed\Http\Resources\PostResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AdCampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'post' => $this->whenLoaded('post', fn () => PostResource::make($this->post)),
            'status' => $this->status->value,
            'budget' => $this->budget,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
