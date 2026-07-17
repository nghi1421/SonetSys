<?php

declare(strict_types=1);

namespace App\Modules\Story\Domain\Models;

use App\Core\Auth\Domain\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StoryView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'story_id',
        'viewer_id',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    public function viewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }
}
