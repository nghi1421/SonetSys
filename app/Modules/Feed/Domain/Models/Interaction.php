<?php

declare(strict_types=1);

namespace App\Modules\Feed\Domain\Models;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Feed\Domain\Enums\InteractionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class Interaction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'interactable_type',
        'interactable_id',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'type' => InteractionType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interactable(): MorphTo
    {
        return $this->morphTo();
    }
}
