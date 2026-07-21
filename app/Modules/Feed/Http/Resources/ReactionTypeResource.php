<?php

declare(strict_types=1);

namespace App\Modules\Feed\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReactionTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'label' => $this->label,
            'emoji' => $this->emoji,
            'icon_url' => $this->icon_url,
            'sort_order' => $this->sort_order,
        ];
    }
}
