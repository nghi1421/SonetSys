<?php

declare(strict_types=1);

namespace App\Modules\Menu\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'slug' => $this->slug,
            'position' => $this->position,
            'is_home' => (bool) $this->is_home,
            'static_page' => $this->whenLoaded(
                'staticPage',
                fn () => $this->staticPage !== null ? StaticPageResource::make($this->staticPage) : null,
            ),
        ];
    }
}
