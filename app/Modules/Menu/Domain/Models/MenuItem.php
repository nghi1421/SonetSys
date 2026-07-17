<?php

declare(strict_types=1);

namespace App\Modules\Menu\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'label',
        'slug',
        'position',
        'is_home',
        'static_page_id',
    ];

    protected function casts(): array
    {
        return [
            'is_home' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function staticPage(): BelongsTo
    {
        return $this->belongsTo(StaticPage::class);
    }
}
