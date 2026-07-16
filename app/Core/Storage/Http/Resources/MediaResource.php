<?php

declare(strict_types=1);

namespace App\Core\Storage\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expects the controller to have set a transient `url` attribute on the
 * Media instance (via MediaService::url()) before wrapping it — the model
 * itself has no relationship to the storage disk needed to build one.
 */
final class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'url' => $this->url,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'mediable_type' => $this->mediable_type,
            'mediable_id' => $this->mediable_id,
            'uploaded_by' => [
                'id' => $this->whenLoaded('uploader', fn () => $this->uploader->id),
                'name' => $this->whenLoaded('uploader', fn () => $this->uploader->name),
            ],
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
