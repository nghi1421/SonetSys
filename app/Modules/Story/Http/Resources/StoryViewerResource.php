<?php

declare(strict_types=1);

namespace App\Modules\Story\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StoryViewerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'viewer' => [
                'id' => $this->whenLoaded('viewer', fn () => $this->viewer->id),
                'name' => $this->whenLoaded('viewer', fn () => $this->viewer->name),
            ],
            'viewed_at' => $this->viewed_at->toIso8601String(),
        ];
    }
}
