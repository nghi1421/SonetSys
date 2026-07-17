<?php

declare(strict_types=1);

namespace App\Modules\Story\Domain\Models;

use App\Core\Auth\Domain\Models\User;
use App\Core\Storage\Domain\Enums\MediaType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Story extends Model
{
    use HasFactory;

    protected $fillable = [
        'author_id',
        'media_type',
        'media_disk',
        'media_path',
        'caption',
        'published_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'media_type' => MediaType::class,
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function views(): HasMany
    {
        return $this->hasMany(StoryView::class);
    }
}
