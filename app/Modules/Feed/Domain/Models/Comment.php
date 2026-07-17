<?php

declare(strict_types=1);

namespace App\Modules\Feed\Domain\Models;

use App\Core\Auth\Domain\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'post_id',
        'parent_id',
        'author_id',
        'body',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function interactions(): MorphMany
    {
        return $this->morphMany(Interaction::class, 'interactable');
    }
}
