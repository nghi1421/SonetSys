<?php

declare(strict_types=1);

namespace App\Modules\Song\Domain\Models;

use App\Core\Auth\Domain\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Song extends Model
{
    protected $fillable = [
        'title',
        'artist',
        'audio_disk',
        'audio_path',
        'cover_disk',
        'cover_path',
        'duration_sec',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'duration_sec' => 'integer',
        ];
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
