<?php

declare(strict_types=1);

namespace App\Modules\Feed\Domain\Models;

use Illuminate\Database\Eloquent\Model;

final class ReactionType extends Model
{
    protected $fillable = [
        'key',
        'label',
        'emoji',
        'icon_url',
        'icon_disk',
        'icon_path',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
