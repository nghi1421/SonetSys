<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status->value,
            'enabled_modules' => $this->enabled_modules,
            'settings' => $this->settings,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
