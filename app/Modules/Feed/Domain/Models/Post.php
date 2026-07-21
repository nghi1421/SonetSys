<?php

declare(strict_types=1);

namespace App\Modules\Feed\Domain\Models;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Feed\Domain\Enums\MediaType;
use App\Modules\Feed\Domain\Enums\PostVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'author_id',
        'shared_post_id',
        'group_id',
        'body',
        'visibility',
        'metadata',
        'media_type',
        'media_path',
        'media_disk',
        'is_reel',
        'location_name',
        'location_lat',
        'location_lng',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'visibility' => PostVisibility::class,
            'metadata' => 'array',
            'media_type' => MediaType::class,
            'is_reel' => 'boolean',
            'location_lat' => 'float',
            'location_lng' => 'float',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function sharedPost(): BelongsTo
    {
        return $this->belongsTo(self::class, 'shared_post_id');
    }

    public function interactions(): MorphMany
    {
        return $this->morphMany(Interaction::class, 'interactable');
    }

    public function hashtags(): MorphToMany
    {
        return $this->morphToMany(Hashtag::class, 'hashtaggable');
    }

    public function mentions(): MorphToMany
    {
        return $this->morphToMany(User::class, 'mentionable', 'mentions', 'mentionable_id', 'user_id');
    }
}
